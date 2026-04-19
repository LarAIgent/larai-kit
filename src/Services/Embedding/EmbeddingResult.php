<?php

namespace LarAIgent\AiKit\Services\Embedding;

/**
 * Return value of `EmbeddingProvider::embedManyWithUsage()`.
 *
 * Carries the resulting vectors plus the provider-reported token count so
 * callers can emit usage/cost telemetry without a second API call.
 */
class EmbeddingResult
{
    public function __construct(
        /** @var array<int, array<int, float>> Vectors in input order */
        public readonly array $vectors,
        public readonly int $tokens,
    ) {}
}
