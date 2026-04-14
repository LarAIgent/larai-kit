<?php

namespace LarAIgent\AiKit\Events;

use Illuminate\Foundation\Events\Dispatchable;
use LarAIgent\AiKit\Models\Asset;
use LarAIgent\AiKit\Models\Ingestion;

class IngestionStateChanged
{
    use Dispatchable;

    public function __construct(
        public readonly Asset $asset,
        public readonly Ingestion $ingestion,
        public readonly string $state,
        public readonly ?string $error = null,
    ) {}
}
