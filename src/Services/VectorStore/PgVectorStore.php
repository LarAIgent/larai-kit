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
        Document::updateOrCreate(
            ['id' => $metadata['document_id'] ?? null],
            [
                'content' => $metadata['content'] ?? '',
                'embedding' => $embedding,
                'source_name' => $metadata['source_name'] ?? null,
                'source_type' => $metadata['source_type'] ?? null,
                'source_url' => $metadata['source_url'] ?? null,
                'source_meta' => array_filter($metadata, fn ($v) => $v !== null),
            ]
        );
    }

    public function search(array $embedding, int $limit = 5, float $threshold = 0.4): Collection
    {
        $documents = Document::query()
            ->whereNotNull('embedding')
            ->whereVectorSimilarTo('embedding', $embedding)
            ->limit($limit)
            ->get();

        return $documents->map(fn (Document $doc) => [
            'chunk_id' => $doc->id,
            'score' => 1.0, // pgvector orders by similarity, exact score not returned by default
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
