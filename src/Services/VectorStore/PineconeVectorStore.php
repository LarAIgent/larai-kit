<?php

namespace LarAIgent\AiKit\Services\VectorStore;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use LarAIgent\AiKit\Contracts\VectorStore;

class PineconeVectorStore implements VectorStore
{
    protected string $apiKey;
    protected string $host;

    public function __construct()
    {
        $this->apiKey = config('larai-kit.pinecone.api_key', '');
        $this->host = rtrim(config('larai-kit.pinecone.index_host', ''), '/');
    }

    public function upsert(int $chunkId, array $embedding, array $metadata = []): void
    {
        // Filter out any null values — Pinecone rejects them
        $metadata = array_filter($metadata, fn ($v) => $v !== null);

        $this->request('POST', '/vectors/upsert', [
            'vectors' => [
                [
                    'id' => (string) $chunkId,
                    'values' => $embedding,
                    'metadata' => $metadata,
                ],
            ],
        ]);
    }

    public function search(array $embedding, int $limit = 5, float $threshold = 0.4): Collection
    {
        $response = $this->request('POST', '/query', [
            'vector' => $embedding,
            'topK' => $limit,
            'includeMetadata' => true,
        ]);

        $matches = $response['matches'] ?? [];

        return collect($matches)
            ->filter(fn ($m) => ($m['score'] ?? 0) >= $threshold)
            ->map(fn ($m) => [
                'chunk_id' => (int) $m['id'],
                'score' => (float) ($m['score'] ?? 0),
                'content' => $m['metadata']['content'] ?? '',
                'metadata' => $m['metadata'] ?? [],
            ]);
    }

    public function delete(array $chunkIds): void
    {
        if (empty($chunkIds)) {
            return;
        }

        $this->request('POST', '/vectors/delete', [
            'ids' => array_map('strval', $chunkIds),
        ]);
    }

    public function isConfigured(): bool
    {
        return ! empty($this->apiKey) && ! empty($this->host);
    }

    protected function request(string $method, string $path, array $data = []): array
    {
        $response = Http::withHeaders([
            'Api-Key' => $this->apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(30)->{strtolower($method)}($this->host . $path, $data);

        if ($response->failed()) {
            throw new \RuntimeException(
                "Pinecone API error [{$response->status()}]: " . $response->body()
            );
        }

        return $response->json() ?? [];
    }
}
