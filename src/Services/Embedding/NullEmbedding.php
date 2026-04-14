<?php

namespace LarAIgent\AiKit\Services\Embedding;

use LarAIgent\AiKit\Contracts\EmbeddingProvider;

class NullEmbedding implements EmbeddingProvider
{
    public function embed(string $text): array
    {
        return array_fill(0, $this->dimensions(), 0.0);
    }

    public function embedMany(array $texts): array
    {
        return array_map(fn () => $this->embed(''), $texts);
    }

    public function dimensions(): int
    {
        return (int) config('larai-kit.embedding_dimensions', 1536);
    }
}
