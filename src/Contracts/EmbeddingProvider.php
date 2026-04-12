<?php

namespace LarAIgent\AiKit\Contracts;

interface EmbeddingProvider
{
    /**
     * Generate a vector embedding for the given text.
     *
     * @return array<int, float>
     */
    public function embed(string $text): array;

    /**
     * Return the dimensionality of the embedding model.
     */
    public function dimensions(): int;
}
