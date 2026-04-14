<?php

namespace LarAIgent\AiKit\Models;

use Illuminate\Database\Eloquent\Model;

class Usage extends Model
{
    protected $table = 'ai_usage';

    protected $fillable = [
        'type',
        'scope',
        'provider',
        'model',
        'input_tokens',
        'output_tokens',
        'duration_ms',
        'conversation_id',
        'meta',
    ];

    protected $casts = [
        'scope' => 'array',
        'meta' => 'array',
        'input_tokens' => 'integer',
        'output_tokens' => 'integer',
        'duration_ms' => 'integer',
    ];
}
