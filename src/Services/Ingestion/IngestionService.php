<?php

namespace LarAIgent\AiKit\Services\Ingestion;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use LarAIgent\AiKit\Contracts\FileStorage;
use LarAIgent\AiKit\Jobs\FetchUrlAssetJob;
use LarAIgent\AiKit\Jobs\ParseAssetJob;
use LarAIgent\AiKit\Jobs\ProcessTextAssetJob;
use LarAIgent\AiKit\Models\Asset;
use LarAIgent\AiKit\Models\Ingestion;
use LarAIgent\AiKit\Services\Ingestion\Parsers\ParserRegistry;
use RuntimeException;
use Throwable;

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
     * @param array<string, mixed> $scope Tenant scope (e.g. ['chatbot_id' => 42])
     */
    public function ingestFile(UploadedFile $file, array $scope = []): Asset
    {
        $this->validateFile($file);

        $path = $this->storage->store($file);
        $url = $this->storage->url($path);
        $checksum = md5_file($file->getRealPath()) ?: null;
        $mime = strtolower((string) $file->getClientMimeType());

        $asset = Asset::create([
            'source_name' => $this->sanitizeFilename($file->getClientOriginalName()),
            'source_type' => 'file',
            'source_disk' => $this->storage->disk(),
            'source_path' => $path,
            'source_url' => $url,
            'mime' => $mime,
            'size_bytes' => $file->getSize(),
            'checksum' => $checksum,
            'scope' => $this->normalizeScope($scope),
        ]);

        $ingestion = $this->createIngestion($asset);
        ParseAssetJob::dispatch($asset, $ingestion, $scope);

        return $asset->load('ingestion');
    }

    /**
     * Ingest an existing file path from a configured Laravel disk.
     *
     * @param array<string, mixed> $scope Tenant scope (e.g. ['chatbot_id' => 42])
     */
    public function ingestFromDisk(string $disk, string $path, array $scope = []): Asset
    {
        $path = ltrim($path, '/');
        $diskStorage = Storage::disk($disk);

        if (! $diskStorage->exists($path)) {
            throw new RuntimeException("File not found on disk [{$disk}]: {$path}");
        }

        $mime = $this->detectDiskMimeType($disk, $path);
        $this->validateMimeForParsing($mime);

        $asset = Asset::create([
            'source_name' => $this->sanitizeFilename(basename($path)),
            'source_type' => 'file',
            'source_disk' => $disk,
            'source_path' => $path,
            'source_url' => $this->optionalDiskUrl($disk, $path),
            'mime' => $mime,
            'size_bytes' => $this->optionalDiskFileSize($disk, $path),
            'checksum' => $this->checksumForDiskFile($disk, $path),
            'scope' => $this->normalizeScope($scope),
        ]);

        $ingestion = $this->createIngestion($asset);
        ParseAssetJob::dispatch($asset, $ingestion, $scope);

        return $asset->load('ingestion');
    }

    /**
     * Ingest raw text content asynchronously through the same queued pipeline.
     *
     * @param array<string, mixed> $scope Tenant scope (e.g. ['chatbot_id' => 42])
     */
    public function ingestText(string $content, string $name = 'Manual entry', array $scope = []): Asset
    {
        if (trim($content) === '') {
            throw new RuntimeException('Content cannot be empty.');
        }

        $asset = Asset::create([
            'source_name' => $this->sanitizeFilename($name),
            'source_type' => 'text',
            'mime' => 'text/plain',
            'size_bytes' => strlen($content),
            'checksum' => md5($content),
            'scope' => $this->normalizeScope($scope),
        ]);

        $ingestion = $this->createIngestion($asset);
        ProcessTextAssetJob::dispatch($asset, $ingestion, $content, $scope);

        return $asset->load('ingestion');
    }

    /**
     * Ingest content from a URL.
     *
     * Safety validation is synchronous (scheme + SSRF checks).
     * Fetching/parsing/chunking runs asynchronously in a queued job.
     *
     * @param array<string, mixed> $scope Tenant scope
     */
    public function ingestUrl(string $url, array $scope = []): Asset
    {
        $fetcher = $this->urlFetcher ?? app(UrlFetcher::class);
        $fetcher->assertSafe($url);

        $asset = Asset::create([
            'source_name' => $this->extractNameFromUrl($url),
            'source_type' => 'url',
            'source_url' => $url,
            'scope' => $this->normalizeScope($scope),
        ]);

        $ingestion = $this->createIngestion($asset);
        FetchUrlAssetJob::dispatch($asset, $ingestion, $url, $scope);

        return $asset->load('ingestion');
    }

    protected function createIngestion(Asset $asset): Ingestion
    {
        return Ingestion::create([
            'asset_id' => $asset->id,
            'state' => 'queued',
        ]);
    }

    protected function validateFile(UploadedFile $file): void
    {
        $maxMb = (int) config('larai-kit.max_file_size_mb', 20);
        $maxBytes = $maxMb * 1024 * 1024;

        if ($file->getSize() > $maxBytes) {
            throw new RuntimeException("File exceeds maximum size of {$maxMb}MB.");
        }

        $mime = strtolower((string) ($file->getClientMimeType() ?: $file->getMimeType() ?: ''));
        if ($mime === '') {
            $mime = $this->fallbackMimeFromExtension($file->getClientOriginalName());
        }

        $this->validateMimeForParsing($mime);
    }

    protected function validateMimeForParsing(string $mime): void
    {
        $allowed = config('larai-kit.allowed_mime_types', []);

        if (! empty($allowed) && ! in_array($mime, $allowed, true)) {
            throw new RuntimeException(
                "File type not allowed: {$mime}. Allowed: " . implode(', ', $allowed)
            );
        }

        if (! $this->parserRegistry->supports($mime)) {
            $supported = implode(', ', $this->parserRegistry->allSupportedMimeTypes());
            throw new RuntimeException(
                "No parser available for file type: {$mime}. Supported: {$supported}. "
                . 'Install smalot/pdfparser for PDF or phpoffice/phpword for DOCX.'
            );
        }
    }

    protected function detectDiskMimeType(string $disk, string $path): string
    {
        try {
            $mime = Storage::disk($disk)->mimeType($path);
        } catch (Throwable) {
            $mime = null;
        }

        if (is_string($mime) && trim($mime) !== '' && trim($mime) !== 'application/octet-stream') {
            return strtolower(trim($mime));
        }

        return $this->fallbackMimeFromExtension($path);
    }

    protected function fallbackMimeFromExtension(string $path): string
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return match ($extension) {
            'txt' => 'text/plain',
            'md' => 'text/markdown',
            'csv' => 'text/csv',
            'pdf' => 'application/pdf',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'html', 'htm' => 'text/html',
            default => 'application/octet-stream',
        };
    }

    protected function optionalDiskUrl(string $disk, string $path): ?string
    {
        try {
            return Storage::disk($disk)->url($path);
        } catch (Throwable) {
            return null;
        }
    }

    protected function optionalDiskFileSize(string $disk, string $path): ?int
    {
        try {
            return (int) Storage::disk($disk)->size($path);
        } catch (Throwable) {
            return null;
        }
    }

    protected function checksumForDiskFile(string $disk, string $path): ?string
    {
        try {
            $diskStorage = Storage::disk($disk);

            if (method_exists($diskStorage, 'path')) {
                $fullPath = $diskStorage->path($path);
                if (is_string($fullPath) && is_file($fullPath)) {
                    $hash = md5_file($fullPath);
                    if ($hash !== false) {
                        return $hash;
                    }
                }
            }

            $content = $diskStorage->get($path);
            return is_string($content) ? md5($content) : null;
        } catch (Throwable) {
            return null;
        }
    }

    protected function extractNameFromUrl(string $url): string
    {
        $parsed = parse_url($url);
        $name = basename($parsed['path'] ?? '') ?: $parsed['host'] ?? $url;

        return Str::limit(trim($name), 191, '');
    }

    /**
     * @param array<string, mixed> $scope
     * @return array<string, mixed>|null
     */
    protected function normalizeScope(array $scope): ?array
    {
        return ! empty($scope) ? $scope : null;
    }

    protected function sanitizeFilename(string $name): string
    {
        $name = str_replace(['/', '\\', "\0"], '', $name);
        $name = preg_replace('/[^\w.\-\s]/', '', $name) ?? $name;

        return Str::limit(trim($name), 255, '');
    }
}
