<?php

namespace LarAIgent\AiKit\Services\Retrieval;

use Illuminate\Support\Collection;
use LarAIgent\AiKit\Contracts\EmbeddingProvider;
use LarAIgent\AiKit\Contracts\VectorStore;
use LarAIgent\AiKit\Services\FeatureDetector;

class RetrievalService
{
    public function __construct(
        protected EmbeddingProvider $embedder,
        protected VectorStore $vectorStore,
        protected FeatureDetector $features,
    ) {}

    /**
     * Retrieve the most relevant chunks for a user query.
     *
     * @param array<string, mixed> $scope  Tenant scope filter (e.g. ['chatbot_id' => 42])
     */
    public function retrieve(
        string $query,
        ?int $limit = null,
        ?float $threshold = null,
        array $scope = [],
    ): Collection {
        if (! $this->features->ragEnabled()) {
            return collect();
        }

        $limit = $limit ?? (int) config('larai-kit.rag_top_k', 5);
        $threshold = $threshold ?? (float) config('larai-kit.similarity_threshold', 0.4);

        $embedding = $this->embedder->embed($query);

        return $this->vectorStore->search($embedding, $limit, $threshold, $scope)
            ->map(fn (array $result) => [
                'content' => $result['content'] ?? $result['metadata']['content'] ?? '',
                'source_name' => $result['metadata']['source_name'] ?? null,
                'source_url' => $result['metadata']['source_url'] ?? null,
                'source_type' => $result['metadata']['source_type'] ?? null,
                'score' => $result['score'] ?? 0,
            ]);
    }
}
