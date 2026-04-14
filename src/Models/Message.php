<?php

namespace LarAIgent\AiKit\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    protected $table = 'ai_messages';

    protected $fillable = [
        'conversation_id',
        'role',
        'content',
        'sources',
        'input_tokens',
        'output_tokens',
    ];

    protected $casts = [
        'sources' => 'array',
        'input_tokens' => 'integer',
        'output_tokens' => 'integer',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class, 'conversation_id');
    }
}
