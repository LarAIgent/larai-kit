<?php

namespace LarAIgent\AiKit\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use LarAIgent\AiKit\Models\Asset;
use LarAIgent\AiKit\Models\Chunk;
use LarAIgent\AiKit\Models\Ingestion;
use LarAIgent\AiKit\Services\Ingestion\Chunker;
use Throwable;

class ChunkAssetJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly Asset $asset,
        public readonly Ingestion $ingestion,
        public readonly string $text,
        public readonly array $scope = [],
    ) {}

    public function handle(Chunker $chunker): void
    {
        $this->ingestion->markState('chunking');

        try {
            $chunks = $chunker->chunk($this->text);

            if (empty($chunks)) {
                $this->ingestion->markState('failed', 'Chunker produced no chunks.');
                return;
            }

            $chunkIds = [];
            foreach ($chunks as $chunk) {
                $model = Chunk::create([
                    'asset_id' => $this->asset->id,
                    'content' => $chunk['text'],
                    'chunk_index' => $chunk['chunk_index'],
                    'created_at' => now(),
                ]);
                $chunkIds[] = $model->id;
            }

            $this->ingestion->update(['chunk_count' => count($chunkIds)]);

            EmbedChunksJob::dispatch($this->asset, $this->ingestion, $chunkIds, $this->scope);

        } catch (Throwable $e) {
            $this->ingestion->markState('failed', $e->getMessage());
            throw $e;
        }
    }
}
