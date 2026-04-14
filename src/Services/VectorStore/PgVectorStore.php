<?php

namespace LarAIgent\AiKit\Services\VectorStore;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use LarAIgent\AiKit\Contracts\VectorStore;
use LarAIgent\AiKit\Models\Document;

class PgVectorStore implements VectorStore
{
    public function upsert(int $chunkId, array $embedding, array $metadata = []): void
    {
        $this->upsertMany([
            ['chunk_id' => $chunkId, 'embedding' => $embedding, 'metadata' => $metadata],
        ]);
    }

    public function upsertMany(array $items): void
    {
        foreach ($items as $item) {
            $meta = array_filter($item['metadata'] ?? [], fn ($v) => $v !== null);

            Document::create([
                'content' => $meta['content'] ?? '',
                'embedding' => $item['embedding'],
                'source_name' => $meta['source_name'] ?? null,
                'source_type' => $meta['source_type'] ?? null,
                'source_url' => $meta['source_url'] ?? null,
                'source_meta' => $meta,
            ]);
        }
    }

    public function search(array $embedding, int $limit = 5, float $threshold = 0.4, array $scope = []): Collection
    {
        $query = Document::query()->whereNotNull('embedding');

        // Apply tenant scope via source_meta JSON filtering
        foreach ($scope as $key => $value) {
            $query->where("source_meta->{$key}", $value);
        }

        $documents = $query
            ->whereVectorSimilarTo('embedding', $embedding)
            ->limit($limit)
            ->get();

        return $documents->map(fn (Document $doc) => [
            'chunk_id' => $doc->id,
            'score' => 1.0,
            'content' => $doc->content,
            'metadata' => [
                'source_name' => $doc->source_name,
                'source_url' => $doc->source_url,
                'source_type' => $doc->source_type,
            ],
        ]);
    }

    public function delete(array $chunkIds): void
    {
        if (empty($chunkIds)) {
            return;
        }

        Document::whereIn('id', $chunkIds)->delete();
    }

    public function isConfigured(): bool
    {
        if (config('database.default') !== 'pgsql') {
            return false;
        }

        try {
            $result = DB::select("SELECT 1 FROM pg_extension WHERE extname = 'vector' LIMIT 1");
            return ! empty($result);
        } catch (\Throwable) {
            return false;
        }
    }
}
