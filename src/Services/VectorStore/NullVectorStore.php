<?php

namespace LarAIgent\AiKit\Services\VectorStore;

use Illuminate\Support\Collection;
use LarAIgent\AiKit\Contracts\VectorStore;

class NullVectorStore implements VectorStore
{
    public function upsert(int $chunkId, array $embedding, array $metadata = []): void {}

    public function upsertMany(array $items): void {}

    public function search(array $embedding, int $limit = 5, float $threshold = 0.4, array $scope = []): Collection
    {
        return collect();
    }

    public function delete(array $chunkIds): void {}

    public function isConfigured(): bool
    {
        return false;
    }
}
