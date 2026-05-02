<?php

namespace LarAIgent\AiKit\Events;

use Illuminate\Foundation\Events\Dispatchable;
use LarAIgent\AiKit\Models\Asset;
use LarAIgent\AiKit\Models\Ingestion;

/**
 * Fired after an asset's pipeline completes successfully (state = "indexed").
 *
 * Unlike `IngestionStateChanged`, this event is deferred to after-commit when
 * the state transition occurs inside a DB transaction. This keeps listener
 * reads aligned with committed writes across web, CLI, and queue-worker paths.
 *
 * Prefer this over `IngestionStateChanged` if all you care about is
 * "this asset is now searchable."
 */
class AssetIndexed
{
    use Dispatchable;

    public function __construct(
        public readonly Asset $asset,
        public readonly Ingestion $ingestion,
    ) {}
}
