<?php

namespace LarAIgent\AiKit\Events;

use Illuminate\Foundation\Events\Dispatchable;

class ChatCompleted
{
    use Dispatchable;

    public function __construct(
        public readonly string $provider,
        public readonly string $model,
        public readonly int $inputTokens,
        public readonly int $outputTokens,
        public readonly int $durationMs,
        public readonly array $scope = [],
        public readonly ?string $conversationId = null,
        public readonly array $meta = [],
    ) {}
}
