<?php

namespace LarAIgent\AiKit\Services\VectorStore;

use Illuminate\Support\Collection;
use LarAIgent\AiKit\Contracts\VectorStore;

class NullVectorStore implements VectorStore
{
    public function upsert(int $chunkId, array $embedding, array $metadata = []): void
    {
        // No-op
    }

    public function search(array $embedding, int $limit = 5, float $threshold = 0.4): Collection
    {
        return collect();
    }

    public function delete(array $chunkIds): void
    {
        // No-op
    }

    public function isConfigured(): bool
    {
        return false;
    }
}
