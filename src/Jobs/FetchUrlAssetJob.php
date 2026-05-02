<?php

namespace LarAIgent\AiKit\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use LarAIgent\AiKit\Models\Asset;
use LarAIgent\AiKit\Models\Ingestion;
use LarAIgent\AiKit\Services\Ingestion\Parsers\HtmlParser;
use LarAIgent\AiKit\Services\Ingestion\UrlFetcher;
use RuntimeException;
use Throwable;

class FetchUrlAssetJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;

    public function __construct(
        public readonly Asset $asset,
        public readonly Ingestion $ingestion,
        public readonly string $url,
        public readonly array $scope = [],
    ) {}

    public function handle(UrlFetcher $fetcher): void
    {
        set_time_limit(0);
        $this->ingestion->markState('parsing');

        try {
            $result = $fetcher->fetch($this->url);
            $contentType = $result['content_type'] ?? '';
            $body = $result['body'] ?? '';
            $resolvedUrl = $result['url'] ?? $this->url;

            $text = $this->parseBody($contentType, $body);

            if (trim($text) === '') {
                $this->ingestion->markState('failed', 'URL returned empty content after parsing.');
                return;
            }

            $this->asset->update([
                'source_name' => $this->extractNameFromUrl($resolvedUrl),
                'source_url' => $resolvedUrl,
                'mime' => $contentType,
                'size_bytes' => strlen($body),
                'checksum' => md5($body),
            ]);

            ChunkAssetJob::dispatch($this->asset->fresh(), $this->ingestion, $text, $this->scope);
        } catch (Throwable $e) {
            $this->ingestion->markState('failed', $e->getMessage());
            throw $e;
        }
    }

    protected function parseBody(string $contentType, string $body): string
    {
        return match (true) {
            in_array($contentType, ['text/html', 'application/xhtml+xml'], true) =>
                (new HtmlParser())->parse($body),
            in_array($contentType, ['text/plain', 'text/markdown', 'text/csv'], true) =>
                trim($body),
            default => throw new RuntimeException(
                "Unsupported content type from URL: {$contentType}. "
                . 'Supported: text/html, text/plain, text/markdown, text/csv.'
            ),
        };
    }

    protected function extractNameFromUrl(string $url): string
    {
        $parsed = parse_url($url);
        $name = basename($parsed['path'] ?? '') ?: $parsed['host'] ?? $url;

        return Str::limit(trim($name), 191, '');
    }
}
