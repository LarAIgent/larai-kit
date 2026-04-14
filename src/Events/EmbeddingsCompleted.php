<?php

namespace LarAIgent\AiKit\Events;

use Illuminate\Foundation\Events\Dispatchable;

class EmbeddingsCompleted
{
    use Dispatchable;

    public function __construct(
        public readonly string $provider,
        public readonly string $model,
        public readonly int $tokenCount,
        public readonly int $chunkCount,
        public readonly int $durationMs,
        public readonly array $scope = [],
        public readonly array $meta = [],
    ) {}
}
