<?php

namespace LarAIgent\AiKit\Contracts;

use Illuminate\Support\Collection;

interface VectorStore
{
    /**
     * Upsert a single chunk with its embedding.
     *
     * @param array<int, float> $embedding
     * @param array<string, mixed> $metadata
     */
    public function upsert(int $chunkId, array $embedding, array $metadata = []): void;

    /**
     * Upsert multiple chunks in a single batch call.
     * Pinecone accepts 100/request; pgvector supports bulk INSERT.
     *
     * @param array<int, array{chunk_id: int, embedding: array, metadata: array}> $items
     */
    public function upsertMany(array $items): void;

    /**
     * Search for the most similar chunks to the given embedding.
     *
     * @param array<int, float> $embedding
     * @param array<string, mixed> $scope  Tenant/namespace filter
     * @return Collection<int, array{chunk_id: int, score: float, content: string, metadata: array}>
     */
    public function search(array $embedding, int $limit = 5, float $threshold = 0.4, array $scope = []): Collection;

    /**
     * Delete vectors by chunk IDs.
     *
     * @param array<int, int> $chunkIds
     */
    public function delete(array $chunkIds): void;
}
