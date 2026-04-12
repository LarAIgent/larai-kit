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

            $chunkModels = [];
            foreach ($chunks as $chunk) {
                $chunkModels[] = Chunk::create([
                    'asset_id' => $this->asset->id,
                    'content' => $chunk['text'],
                    'chunk_index' => $chunk['chunk_index'],
                    'created_at' => now(),
                ]);
            }

            $this->ingestion->update(['chunk_count' => count($chunkModels)]);

            EmbedChunksJob::dispatch($this->asset, $this->ingestion, collect($chunkModels)->pluck('id')->all());

        } catch (Throwable $e) {
            $this->ingestion->markState('failed', $e->getMessage());
            throw $e;
        }
    }
}
