<?php

namespace LarAIgent\AiKit\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use LarAIgent\AiKit\Contracts\VectorStore;

class DeleteAssetVectorsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        /** @var array<int, int> */
        public readonly array $chunkIds,
        public readonly int $assetId,
    ) {}

    public function handle(VectorStore $vectorStore): void
    {
        $vectorStore->delete($this->chunkIds);

        \LarAIgent\AiKit\Models\Chunk::whereIn('id', $this->chunkIds)->delete();
    }
}
