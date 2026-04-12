<?php

namespace LarAIgent\AiKit\Models;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected $table = 'ai_documents';

    protected $fillable = [
        'content',
        'embedding',
        'source_name',
        'source_type',
        'source_url',
        'source_meta',
    ];

    protected $casts = [
        'embedding' => 'vector',
        'source_meta' => 'array',
    ];
}
