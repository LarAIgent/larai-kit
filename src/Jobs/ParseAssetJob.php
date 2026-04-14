<?php

namespace LarAIgent\AiKit\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use LarAIgent\AiKit\Models\Asset;
use LarAIgent\AiKit\Models\Ingestion;
use LarAIgent\AiKit\Services\Ingestion\Parsers\ParserRegistry;
use Throwable;

class ParseAssetJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;

    public function __construct(
        public readonly Asset $asset,
        public readonly Ingestion $ingestion,
        public readonly array $scope = [],
    ) {}

    public function handle(ParserRegistry $registry): void
    {
        set_time_limit(0);
        $this->ingestion->markState('parsing');

        try {
            $mime = $this->asset->mime ?? 'text/plain';

            if (! $registry->supports($mime)) {
                $supported = implode(', ', $registry->allSupportedMimeTypes());
                $this->ingestion->markState('failed', "Unsupported mime type: {$mime}. Supported: {$supported}");
                return;
            }

            $disk = $this->asset->source_disk;
            $path = $this->asset->source_path;

            if (! $disk || ! $path) {
                $this->ingestion->markState('failed', 'No file path available for parsing.');
                return;
            }

            $fullPath = \Illuminate\Support\Facades\Storage::disk($disk)->path($path);
            $text = $registry->resolve($mime)->parse($fullPath);

            if (empty(trim($text))) {
                $this->ingestion->markState('failed', 'Parser returned empty text.');
                return;
            }

            ChunkAssetJob::dispatch($this->asset, $this->ingestion, $text, $this->scope);

        } catch (Throwable $e) {
            $this->ingestion->markState('failed', $e->getMessage());
            throw $e;
        }
    }
}
