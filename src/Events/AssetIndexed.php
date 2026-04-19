<?php

namespace LarAIgent\AiKit\Events;

use Illuminate\Foundation\Events\Dispatchable;
use LarAIgent\AiKit\Models\Asset;
use LarAIgent\AiKit\Models\Ingestion;

/**
 * Fired after an asset's pipeline completes successfully (state = "indexed").
 *
 * Unlike `IngestionStateChanged`, this event is deferred via `afterResponse()`
 * in a web request, so listeners run after the caller has committed its outer
 * DB transaction and stored `$asset->id` against their own domain rows.
 *
 * In CLI / queue-worker contexts, `afterResponse()` degrades to immediate
 * dispatch — which is still correct because the worker has finished its job
 * by the time the event fires.
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
