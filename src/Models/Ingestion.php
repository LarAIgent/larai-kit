<?php

namespace LarAIgent\AiKit\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use LarAIgent\AiKit\Events\AssetFailed;
use LarAIgent\AiKit\Events\AssetIndexed;
use LarAIgent\AiKit\Events\IngestionStateChanged;

class Ingestion extends Model
{
    protected $table = 'ai_ingestions';

    protected $fillable = [
        'asset_id',
        'state',
        'error',
        'chunk_count',
    ];

    protected $casts = [
        'chunk_count' => 'integer',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'asset_id');
    }

    public function isPending(): bool
    {
        return in_array($this->state, ['queued', 'parsing', 'chunking', 'embedding']);
    }

    public function isFailed(): bool
    {
        return $this->state === 'failed';
    }

    public function isComplete(): bool
    {
        return $this->state === 'indexed';
    }

    public function markState(string $state, ?string $error = null): void
    {
        // Guard: cannot mark "indexed" with zero chunks
        if ($state === 'indexed' && ($this->chunk_count ?? 0) === 0) {
            $state = 'failed';
            $error = $error ?? 'Ingestion completed but no chunks were indexed.';
        }

        $this->update([
            'state' => $state,
            'error' => $error,
        ]);

        $asset = $this->asset ?? $this->asset()->first();

        // Fire immediate lifecycle event for progress tracking
        IngestionStateChanged::dispatch($asset, $this, $state, $error);

        // Fire terminal events after commit when inside a DB transaction.
        if ($state === 'indexed') {
            $this->dispatchTerminal(fn () => AssetIndexed::dispatch($asset, $this));
        } elseif ($state === 'failed') {
            $this->dispatchTerminal(
                fn () => AssetFailed::dispatch($asset, $this, $error ?? 'unknown error'),
            );
        }
    }

    /**
     * Dispatch a terminal event after transaction commit when inside a DB
     * transaction. Outside transactions, dispatch immediately.
     */
    protected function dispatchTerminal(callable $dispatcher): void
    {
        if (DB::transactionLevel() === 0) {
            $dispatcher();

            return;
        }

        DB::afterCommit($dispatcher);
    }
}
