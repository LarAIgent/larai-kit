<?php

namespace LarAIgent\AiKit\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use LarAIgent\AiKit\Models\Asset;
use LarAIgent\AiKit\Models\Ingestion;
use Throwable;

class ProcessTextAssetJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly Asset $asset,
        public readonly Ingestion $ingestion,
        public readonly string $content,
        public readonly array $scope = [],
    ) {}

    public function handle(): void
    {
        $this->ingestion->markState('parsing');

        try {
            $text = trim($this->content);

            if ($text === '') {
                $this->ingestion->markState('failed', 'Content cannot be empty.');
                return;
            }

            ChunkAssetJob::dispatch($this->asset, $this->ingestion, $text, $this->scope);
        } catch (Throwable $e) {
            $this->ingestion->markState('failed', $e->getMessage());
            throw $e;
        }
    }
}
