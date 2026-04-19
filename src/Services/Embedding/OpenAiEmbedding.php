<?php

namespace LarAIgent\AiKit\Services\Embedding;

use Illuminate\Support\Str;
use Laravel\Ai\Embeddings;
use Laravel\Ai\Responses\EmbeddingsResponse;
use LarAIgent\AiKit\Contracts\EmbeddingProvider;

class OpenAiEmbedding implements EmbeddingProvider
{
    public function embed(string $text): array
    {
        $result = Str::of($text)->toEmbeddings();

        // The macro may return a plain array or an object with toArray()
        if (is_array($result)) {
            return array_values($result);
        }

        if (is_object($result) && method_exists($result, 'toArray')) {
            return array_values($result->toArray());
        }

        return (array) $result;
    }

    public function embedMany(array $texts): array
    {
        return $this->embedManyWithUsage($texts)->vectors;
    }

    /**
     * Batch-embed inputs and return vectors plus provider-reported token count.
     *
     * Uses `Embeddings::for($texts)->generate()` directly so we receive the
     * full `EmbeddingsResponse` including `$tokens`, instead of the
     * `Str::toEmbeddings()` macro which discards it.
     */
    public function embedManyWithUsage(array $texts): EmbeddingResult
    {
        if (empty($texts)) {
            return new EmbeddingResult(vectors: [], tokens: 0);
        }

        // OpenAI accepts up to 2048 inputs per call; keep batches modest so
        // we don't blow through per-request token limits for large corpora.
        $batchSize = 96;
        $allVectors = [];
        $totalTokens = 0;

        foreach (array_chunk($texts, $batchSize) as $batch) {
            /** @var EmbeddingsResponse $response */
            $response = Embeddings::for(array_values($batch))->generate();

            foreach ($response->embeddings as $vector) {
                $allVectors[] = array_values($vector);
            }

            $totalTokens += (int) ($response->tokens ?? 0);
        }

        return new EmbeddingResult(vectors: $allVectors, tokens: $totalTokens);
    }

    public function dimensions(): int
    {
        return (int) config('larai-kit.embedding_dimensions', 1536);
    }
}
