<?php

namespace LarAIgent\AiKit\Events;

use Illuminate\Foundation\Events\Dispatchable;
use LarAIgent\AiKit\Models\Asset;
use LarAIgent\AiKit\Models\Ingestion;

/**
 * Fired after an asset's pipeline terminates with state = "failed".
 *
 * Deferred to after-commit when a DB transaction is open so listeners observe
 * committed state consistently in web, CLI, and queue-worker contexts.
 *
 * Prefer this over `IngestionStateChanged` when you only need terminal
 * failures (alerts, notifications, retry orchestration).
 */
class AssetFailed
{
    use Dispatchable;

    public function __construct(
        public readonly Asset $asset,
        public readonly Ingestion $ingestion,
        public readonly string $error,
    ) {}
}
