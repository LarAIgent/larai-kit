<?php

namespace LarAIgent\AiKit\Contracts;

use Illuminate\Support\Collection;

interface VectorStore
{
    /**
     * Upsert a chunk with its embedding into the store.
     *
     * @param array<int, float> $embedding
     * @param array<string, mixed> $metadata
     */
    public function upsert(int $chunkId, array $embedding, array $metadata = []): void;

    /**
     * Search for the most similar chunks to the given embedding.
     *
     * @param array<int, float> $embedding
     * @return Collection<int, array{chunk_id: int, score: float, content: string, metadata: array}>
     */
    public function search(array $embedding, int $limit = 5, float $threshold = 0.4): Collection;

    /**
     * Delete vectors by chunk IDs.
     *
     * @param array<int, int> $chunkIds
     */
    public function delete(array $chunkIds): void;
}
