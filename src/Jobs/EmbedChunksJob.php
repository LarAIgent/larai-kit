<?php

namespace LarAIgent\AiKit\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use LarAIgent\AiKit\Contracts\EmbeddingProvider;
use LarAIgent\AiKit\Contracts\VectorStore;
use LarAIgent\AiKit\Events\EmbeddingsCompleted;
use LarAIgent\AiKit\Models\Asset;
use LarAIgent\AiKit\Models\Chunk;
use LarAIgent\AiKit\Models\Ingestion;
use Throwable;

class EmbedChunksJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300; // 5 minutes — embedding can be slow

    public function __construct(
        public readonly Asset $asset,
        public readonly Ingestion $ingestion,
        /** @var array<int, int> */
        public readonly array $chunkIds,
        public readonly array $scope = [],
    ) {}

    public function handle(EmbeddingProvider $embedder, VectorStore $vectorStore): void
    {
        set_time_limit(0); // Prevent PHP timeout for sync queue

        $this->ingestion->markState('embedding');
        $startTime = microtime(true);

        try {
            $chunks = Chunk::whereIn('id', $this->chunkIds)->get();

            if ($chunks->isEmpty()) {
                $this->ingestion->markState('failed', 'No chunks found to embed.');
                return;
            }

            // Batch embed all texts at once. Prefer the usage-aware variant
            // when the provider exposes it so we can emit real token counts;
            // fall back to `embedMany()` for third-party implementations.
            $texts = $chunks->pluck('content')->all();
            $tokenCount = 0;

            if (method_exists($embedder, 'embedManyWithUsage')) {
                $result = $embedder->embedManyWithUsage($texts);
                $embeddings = $result->vectors;
                $tokenCount = (int) ($result->tokens ?? 0);
            } else {
                $embeddings = $embedder->embedMany($texts);
            }

            // Prepare batch upsert items
            $upsertItems = [];
            foreach ($chunks->values() as $i => $chunk) {
                $embedding = $embeddings[$i] ?? [];

                if (empty($embedding)) {
                    continue;
                }

                // Store on chunk record (for pgvector if column exists)
                try {
                    $chunk->update(['embedding' => $embedding]);
                } catch (\Throwable) {
                    // Embedding column may not exist on MySQL
                }

                // Build metadata — never pass null values
                $metadata = array_filter([
                    'content' => mb_substr($chunk->content, 0, 500), // Pinecone metadata limit
                    'asset_id' => $this->asset->id,
                    'chunk_id' => $chunk->id,
                    'chunk_index' => $chunk->chunk_index,
                    'source_name' => $this->asset->source_name,
                    'source_type' => $this->asset->source_type,
                    'source_url' => $this->asset->source_url,
                    'mime' => $this->asset->mime,
                ], fn ($v) => $v !== null);

                // Merge scope into metadata for tenant filtering
                foreach ($this->scope as $key => $value) {
                    $metadata[$key] = $value;
                }

                $upsertItems[] = [
                    'chunk_id' => $chunk->id,
                    'embedding' => $embedding,
                    'metadata' => $metadata,
                ];
            }

            if (empty($upsertItems)) {
                $this->ingestion->markState('failed', 'All embeddings were empty.');
                return;
            }

            // Batch upsert to vector store
            $vectorStore->upsertMany($upsertItems);

            $this->ingestion->update(['chunk_count' => count($upsertItems)]);
            $this->ingestion->markState('indexed');

            $durationMs = (int) round((microtime(true) - $startTime) * 1000);
            EmbeddingsCompleted::dispatch(
                provider: config('larai-kit.ai_provider', 'openai'),
                model: config('larai-kit.models.embedding', 'text-embedding-3-small'),
                tokenCount: $tokenCount,
                chunkCount: count($upsertItems),
                durationMs: $durationMs,
                scope: $this->scope,
            );

        } catch (Throwable $e) {
            $this->ingestion->markState('failed', $e->getMessage());
            throw $e;
        }
    }
}
