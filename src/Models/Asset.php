<?php

namespace LarAIgent\AiKit\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use LarAIgent\AiKit\Jobs\DeleteAssetVectorsJob;

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
        'scope',
    ];

    protected $casts = [
        'size_bytes' => 'integer',
        'tags' => 'array',
        'scope' => 'array',
    ];

    protected static function booted(): void
    {
        static::deleting(function (self $asset) {
            $chunkIds = $asset->chunks()->pluck('id')->all();

            if (empty($chunkIds)) {
                return;
            }

            DeleteAssetVectorsJob::dispatch($chunkIds, (int) $asset->id)->afterCommit();
        });
    }

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
