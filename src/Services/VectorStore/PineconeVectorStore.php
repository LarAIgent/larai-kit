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
        $this->upsertMany([
            ['chunk_id' => $chunkId, 'embedding' => $embedding, 'metadata' => $metadata],
        ]);
    }

    public function upsertMany(array $items): void
    {
        if (empty($items)) {
            return;
        }

        // Pinecone accepts up to 100 vectors per request
        foreach (array_chunk($items, 100) as $batch) {
            $vectors = [];
            foreach ($batch as $item) {
                $metadata = array_filter($item['metadata'] ?? [], fn ($v) => $v !== null);
                // Pinecone metadata values must be string, number, boolean, or list of strings
                $metadata = $this->sanitizeMetadata($metadata);

                $vectors[] = [
                    'id' => (string) $item['chunk_id'],
                    'values' => $item['embedding'],
                    'metadata' => $metadata,
                ];
            }

            $this->request('POST', '/vectors/upsert', ['vectors' => $vectors]);
        }
    }

    public function search(array $embedding, int $limit = 5, float $threshold = 0.4, array $scope = []): Collection
    {
        $payload = [
            'vector' => $embedding,
            'topK' => $limit,
            'includeMetadata' => true,
        ];

        // Apply tenant scope as Pinecone metadata filter
        if (! empty($scope)) {
            $filter = [];
            foreach ($scope as $key => $value) {
                $filter[$key] = ['$eq' => $value];
            }
            $payload['filter'] = $filter;
        }

        $response = $this->request('POST', '/query', $payload);
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
        $maxAttempts = (int) config('larai-kit.retry.max_attempts', 3);
        $baseDelay = (int) config('larai-kit.retry.base_delay_ms', 1000);
        $retryStatuses = config('larai-kit.retry.on_status', [429, 500, 502, 503, 504]);

        $attempt = 0;
        $lastException = null;

        while ($attempt < $maxAttempts) {
            $attempt++;

            try {
                $response = Http::withHeaders([
                    'Api-Key' => $this->apiKey,
                    'Content-Type' => 'application/json',
                ])->timeout(30)->{strtolower($method)}($this->host . $path, $data);

                if ($response->successful()) {
                    return $response->json() ?? [];
                }

                if (! in_array($response->status(), $retryStatuses) || $attempt >= $maxAttempts) {
                    throw new \RuntimeException(
                        "Pinecone API error [{$response->status()}]: " . $response->body()
                    );
                }
            } catch (\RuntimeException $e) {
                $lastException = $e;
                if ($attempt >= $maxAttempts) {
                    throw $e;
                }
            }

            // Exponential backoff with jitter
            $delay = $baseDelay * (2 ** ($attempt - 1));
            $jitter = random_int(0, (int) ($delay * 0.25));
            usleep(($delay + $jitter) * 1000);
        }

        throw $lastException ?? new \RuntimeException('Pinecone request failed after retries.');
    }

    /**
     * Ensure metadata values are types Pinecone accepts.
     */
    protected function sanitizeMetadata(array $metadata): array
    {
        $clean = [];
        foreach ($metadata as $key => $value) {
            if (is_string($value) || is_int($value) || is_float($value) || is_bool($value)) {
                $clean[$key] = $value;
            } elseif (is_array($value) && ! empty($value) && is_string(reset($value))) {
                $clean[$key] = array_values($value);
            }
            // Skip null, objects, nested arrays — Pinecone rejects them
        }
        return $clean;
    }
}
