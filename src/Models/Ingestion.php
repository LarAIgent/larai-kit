<?php

namespace LarAIgent\AiKit\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        $this->update([
            'state' => $state,
            'error' => $error,
        ]);
    }
}
