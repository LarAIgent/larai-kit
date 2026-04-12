<?php

namespace LarAIgent\AiKit\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Chunk extends Model
{
    public $timestamps = false;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;

    protected $table = 'ai_chunks';

    protected $fillable = [
        'asset_id',
        'content',
        'embedding',
        'chunk_index',
        'page',
        'time_start_ms',
        'time_end_ms',
    ];

    protected $casts = [
        'embedding' => 'vector',
        'chunk_index' => 'integer',
        'page' => 'integer',
        'time_start_ms' => 'integer',
        'time_end_ms' => 'integer',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'asset_id');
    }
}
