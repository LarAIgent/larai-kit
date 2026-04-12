<?php

namespace LarAIgent\AiKit\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Asset extends Model
{
    protected $table = 'ai_assets';

    protected $fillable = [
        'source_name',
        'source_type',
        'source_disk',
        'source_path',
        'source_url',
        'mime',
        'size_bytes',
        'checksum',
        'tags',
    ];

    protected $casts = [
        'size_bytes' => 'integer',
        'tags' => 'array',
    ];

    public function chunks(): HasMany
    {
        return $this->hasMany(Chunk::class, 'asset_id');
    }

    public function ingestion(): HasOne
    {
        return $this->hasOne(Ingestion::class, 'asset_id')->latestOfMany();
    }

    public function ingestions(): HasMany
    {
        return $this->hasMany(Ingestion::class, 'asset_id');
    }
}
