<?php

namespace LarAIgent\AiKit\Services\Embedding;

use Illuminate\Support\Str;
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
        if (empty($texts)) {
            return [];
        }

        // Batch in groups of 32 (OpenAI supports up to 2048 per call)
        $batchSize = 32;
        $allVectors = [];

        foreach (array_chunk($texts, $batchSize) as $batch) {
            foreach ($batch as $text) {
                $allVectors[] = $this->embed($text);
            }
        }

        return $allVectors;
    }

    public function dimensions(): int
    {
        return (int) config('larai-kit.embedding_dimensions', 1536);
    }
}
