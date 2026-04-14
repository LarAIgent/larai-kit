<?php

namespace LarAIgent\AiKit\Services\Ingestion;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use LarAIgent\AiKit\Contracts\FileStorage;
use LarAIgent\AiKit\Jobs\EmbedChunksJob;
use LarAIgent\AiKit\Jobs\ParseAssetJob;
use LarAIgent\AiKit\Models\Asset;
use LarAIgent\AiKit\Models\Chunk;
use LarAIgent\AiKit\Models\Ingestion;
use LarAIgent\AiKit\Services\Ingestion\Parsers\ParserRegistry;
use RuntimeException;

class IngestionService
{
    public function __construct(
        protected FileStorage $storage,
        protected ParserRegistry $parserRegistry,
        protected ?UrlFetcher $urlFetcher = null,
    ) {}

    /**
     * Ingest a file upload.
     *
     * @param array<string, mixed> $scope  Tenant scope (e.g. ['chatbot_id' => 42])
     */
    public function ingestFile(UploadedFile $file, array $scope = []): Asset
    {
        $this->validateFile($file);

        $path = $this->storage->store($file);
        $url = $this->storage->url($path);
        $checksum = md5_file($file->getRealPath());

        $asset = Asset::create([
            'source_name' => $this->sanitizeFilename($file->getClientOriginalName()),
            'source_type' => 'file',
            'source_disk' => $this->storage->disk(),
            'source_path' => $path,
            'source_url' => $url,
            'mime' => $file->getClientMimeType(),
            'size_bytes' => $file->getSize(),
            'checksum' => $checksum,
            'scope' => ! empty($scope) ? $scope : null,
        ]);

        $ingestion = Ingestion::create([
            'asset_id' => $asset->id,
            'state' => 'queued',
        ]);

        ParseAssetJob::dispatch($asset, $ingestion, $scope);

        return $asset;
    }

    /**
     * Ingest raw text content.
     *
     * @param array<string, mixed> $scope  Tenant scope (e.g. ['chatbot_id' => 42])
     */
    public function ingestText(string $content, string $name = 'Manual entry', array $scope = []): Asset
    {
        if (empty(trim($content))) {
            throw new RuntimeException('Content cannot be empty.');
        }

        $asset = Asset::create([
            'source_name' => $name,
            'source_type' => 'text',
            'mime' => 'text/plain',
            'size_bytes' => strlen($content),
            'checksum' => md5($content),
            'scope' => ! empty($scope) ? $scope : null,
        ]);

        $ingestion = Ingestion::create([
            'asset_id' => $asset->id,
            'state' => 'queued',
        ]);

        $ingestion->markState('parsing');

        $chunker = app(Chunker::class);
        $chunks = $chunker->chunk($content);

        if (empty($chunks)) {
            $ingestion->markState('failed', 'Chunker produced no chunks.');
            return $asset;
        }

        $ingestion->markState('chunking');

        $chunkIds = [];
        foreach ($chunks as $chunk) {
            $model = Chunk::create([
                'asset_id' => $asset->id,
                'content' => $chunk['text'],
                'chunk_index' => $chunk['chunk_index'],
                'created_at' => now(),
            ]);
            $chunkIds[] = $model->id;
        }

        $ingestion->update(['chunk_count' => count($chunkIds)]);

        EmbedChunksJob::dispatch($asset, $ingestion, $chunkIds, $scope);

        return $asset;
    }

    /**
     * Ingest content from a URL.
     *
     * @param array<string, mixed> $scope  Tenant scope
     */
    public function ingestUrl(string $url, array $scope = []): Asset
    {
        $fetcher = $this->urlFetcher ?? app(UrlFetcher::class);
        $result = $fetcher->fetch($url);

        $contentType = $result['content_type'];
        $body = $result['body'];

        // Route by content type to extract text
        $text = match (true) {
            in_array($contentType, ['text/html', 'application/xhtml+xml']) =>
                (new Parsers\HtmlParser())->parse($body),
            in_array($contentType, ['text/plain', 'text/markdown', 'text/csv']) =>
                trim($body),
            default => throw new RuntimeException(
                "Unsupported content type from URL: {$contentType}. "
                . "Supported: text/html, text/plain, text/markdown, text/csv."
            ),
        };

        if (empty(trim($text))) {
            throw new RuntimeException('URL returned empty content after parsing.');
        }

        // Extract a name from the URL
        $parsed = parse_url($url);
        $name = basename($parsed['path'] ?? '') ?: $parsed['host'] ?? $url;

        $asset = Asset::create([
            'source_name' => Str::limit($name, 191, ''),
            'source_type' => 'url',
            'source_url' => $url,
            'mime' => $contentType,
            'size_bytes' => strlen($body),
            'checksum' => md5($body),
            'scope' => ! empty($scope) ? $scope : null,
        ]);

        $ingestion = Ingestion::create([
            'asset_id' => $asset->id,
            'state' => 'queued',
        ]);

        // Same flow as ingestText — chunk then embed
        $ingestion->markState('parsing');

        $chunker = app(Chunker::class);
        $chunks = $chunker->chunk($text);

        if (empty($chunks)) {
            $ingestion->markState('failed', 'Chunker produced no chunks from URL content.');
            return $asset;
        }

        $ingestion->markState('chunking');

        $chunkIds = [];
        foreach ($chunks as $chunk) {
            $model = Chunk::create([
                'asset_id' => $asset->id,
                'content' => $chunk['text'],
                'chunk_index' => $chunk['chunk_index'],
                'created_at' => now(),
            ]);
            $chunkIds[] = $model->id;
        }

        $ingestion->update(['chunk_count' => count($chunkIds)]);

        EmbedChunksJob::dispatch($asset, $ingestion, $chunkIds, $scope);

        return $asset;
    }

    protected function validateFile(UploadedFile $file): void
    {
        $maxMb = (int) config('larai-kit.max_file_size_mb', 20);
        $maxBytes = $maxMb * 1024 * 1024;

        if ($file->getSize() > $maxBytes) {
            throw new RuntimeException("File exceeds maximum size of {$maxMb}MB.");
        }

        $allowed = config('larai-kit.allowed_mime_types', []);
        $mime = $file->getClientMimeType();

        if (! empty($allowed) && ! in_array($mime, $allowed, true)) {
            throw new RuntimeException(
                "File type not allowed: {$mime}. Allowed: " . implode(', ', $allowed)
            );
        }

        if (! $this->parserRegistry->supports($mime)) {
            $supported = implode(', ', $this->parserRegistry->allSupportedMimeTypes());
            throw new RuntimeException(
                "No parser available for file type: {$mime}. Supported: {$supported}. "
                . "Install smalot/pdfparser for PDF or phpoffice/phpword for DOCX."
            );
        }
    }

    protected function sanitizeFilename(string $name): string
    {
        $name = str_replace(['/', '\\', "\0"], '', $name);
        $name = preg_replace('/[^\w.\-\s]/', '', $name);

        return Str::limit(trim($name), 255, '');
    }
}
