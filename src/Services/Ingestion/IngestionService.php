<?php

namespace LarAIgent\AiKit\Services\Ingestion;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use LarAIgent\AiKit\Contracts\FileStorage;
use LarAIgent\AiKit\Jobs\ParseAssetJob;
use LarAIgent\AiKit\Models\Asset;
use LarAIgent\AiKit\Models\Ingestion;
use LarAIgent\AiKit\Services\Ingestion\Parsers\ParserRegistry;
use RuntimeException;

class IngestionService
{
    public function __construct(
        protected FileStorage $storage,
        protected ParserRegistry $parserRegistry,
    ) {}

    /**
     * Ingest a file upload: validate, store, create records, dispatch pipeline.
     */
    public function ingestFile(UploadedFile $file): Asset
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
        ]);

        $ingestion = Ingestion::create([
            'asset_id' => $asset->id,
            'state' => 'queued',
        ]);

        ParseAssetJob::dispatch($asset, $ingestion);

        return $asset;
    }

    /**
     * Ingest raw text content directly.
     */
    public function ingestText(string $content, string $name = 'Manual entry'): Asset
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
        ]);

        $ingestion = Ingestion::create([
            'asset_id' => $asset->id,
            'state' => 'queued',
        ]);

        // For raw text, skip file parse step — go directly to chunking
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
            $model = \LarAIgent\AiKit\Models\Chunk::create([
                'asset_id' => $asset->id,
                'content' => $chunk['text'],
                'chunk_index' => $chunk['chunk_index'],
                'created_at' => now(),
            ]);
            $chunkIds[] = $model->id;
        }

        $ingestion->update(['chunk_count' => count($chunkIds)]);

        \LarAIgent\AiKit\Jobs\EmbedChunksJob::dispatch($asset, $ingestion, $chunkIds);

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
            throw new RuntimeException("File type not allowed: {$mime}");
        }

        if (! $this->parserRegistry->supports($mime)) {
            throw new RuntimeException("No parser available for file type: {$mime}");
        }
    }

    protected function sanitizeFilename(string $name): string
    {
        // Strip path separators and null bytes
        $name = str_replace(['/', '\\', "\0"], '', $name);

        // Remove any non-ASCII characters except basic punctuation
        $name = preg_replace('/[^\w.\-\s]/', '', $name);

        return Str::limit(trim($name), 255, '');
    }
}
