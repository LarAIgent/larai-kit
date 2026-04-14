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
     * Generate embeddings for multiple texts in a single API call.
     * Major providers (OpenAI, Cohere, Voyage) support batched inputs.
     *
     * @param  string[]  $texts
     * @return array<int, array<int, float>>  Vectors in input order
     */
    public function embedMany(array $texts): array;

    /**
     * Return the dimensionality of the embedding model.
     */
    public function dimensions(): int;
}
