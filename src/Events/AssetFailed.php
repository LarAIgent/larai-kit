<?php

namespace LarAIgent\AiKit\Events;

use Illuminate\Foundation\Events\Dispatchable;
use LarAIgent\AiKit\Models\Asset;
use LarAIgent\AiKit\Models\Ingestion;

/**
 * Fired after an asset's pipeline terminates with state = "failed".
 *
 * Deferred via `afterResponse()` in a web request so listeners run after the
 * caller has committed. In CLI / queue-worker contexts it's equivalent to
 * immediate dispatch.
 *
 * Prefer this over `IngestionStateChanged` when you only need to know about
 * terminal failures (for alerts, user notifications, retry logic).
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
