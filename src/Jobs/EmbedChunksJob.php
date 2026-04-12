<?php

namespace LarAIgent\AiKit\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use LarAIgent\AiKit\Contracts\EmbeddingProvider;
use LarAIgent\AiKit\Contracts\VectorStore;
use LarAIgent\AiKit\Models\Asset;
use LarAIgent\AiKit\Models\Chunk;
use LarAIgent\AiKit\Models\Ingestion;
use Throwable;

class EmbedChunksJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly Asset $asset,
        public readonly Ingestion $ingestion,
        /** @var array<int, int> */
        public readonly array $chunkIds,
    ) {}

    public function handle(EmbeddingProvider $embedder, VectorStore $vectorStore): void
    {
        $this->ingestion->markState('embedding');

        try {
            $chunks = Chunk::whereIn('id', $this->chunkIds)->get();

            foreach ($chunks as $chunk) {
                $embedding = $embedder->embed($chunk->content);

                // Store embedding on the chunk record (for pgvector if column exists)
                try {
                    $chunk->update(['embedding' => $embedding]);
                } catch (\Throwable) {
                    // Embedding column may not exist on MySQL — that's fine
                }

                // Build metadata — never pass null values
                $metadata = array_filter([
                    'content' => $chunk->content,
                    'asset_id' => $this->asset->id,
                    'chunk_id' => $chunk->id,
                    'chunk_index' => $chunk->chunk_index,
                    'source_name' => $this->asset->source_name,
                    'source_type' => $this->asset->source_type,
                    'source_url' => $this->asset->source_url,
                    'source_disk' => $this->asset->source_disk,
                    'source_path' => $this->asset->source_path,
                    'mime' => $this->asset->mime,
                    'size_bytes' => $this->asset->size_bytes,
                    'page' => $chunk->page,
                ], fn ($v) => $v !== null);

                // Upsert into the configured vector store (Pinecone or pgvector)
                $vectorStore->upsert($chunk->id, $embedding, $metadata);
            }

            $this->ingestion->markState('indexed');

        } catch (Throwable $e) {
            $this->ingestion->markState('failed', $e->getMessage());
            throw $e;
        }
    }
}
