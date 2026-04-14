<?php

namespace LarAIgent\AiKit\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Conversation extends Model
{
    protected $table = 'ai_conversations';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'title',
        'scope',
        'metadata',
    ];

    protected $casts = [
        'scope' => 'array',
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->id = $model->id ?? (string) Str::uuid7();
        });
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'conversation_id')->orderBy('created_at');
    }

    /**
     * Filter conversations by scope.
     */
    public function scopeForScope(Builder $query, array $scope): Builder
    {
        foreach ($scope as $key => $value) {
            $query->where("scope->{$key}", $value);
        }

        return $query;
    }
}
