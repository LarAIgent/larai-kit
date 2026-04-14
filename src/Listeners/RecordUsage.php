<?php

namespace LarAIgent\AiKit\Listeners;

use LarAIgent\AiKit\Events\ChatCompleted;
use LarAIgent\AiKit\Events\EmbeddingsCompleted;
use LarAIgent\AiKit\Models\Usage;

class RecordUsage
{
    public function handleChat(ChatCompleted $event): void
    {
        if (! config('larai-kit.track_usage', false)) {
            return;
        }

        Usage::create([
            'type' => 'chat',
            'scope' => $event->scope ?: null,
            'provider' => $event->provider,
            'model' => $event->model,
            'input_tokens' => $event->inputTokens,
            'output_tokens' => $event->outputTokens,
            'duration_ms' => $event->durationMs,
            'conversation_id' => $event->conversationId,
            'meta' => $event->meta ?: null,
        ]);
    }

    public function handleEmbeddings(EmbeddingsCompleted $event): void
    {
        if (! config('larai-kit.track_usage', false)) {
            return;
        }

        Usage::create([
            'type' => 'embedding',
            'scope' => $event->scope ?: null,
            'provider' => $event->provider,
            'model' => $event->model,
            'input_tokens' => $event->tokenCount,
            'output_tokens' => 0,
            'duration_ms' => $event->durationMs,
            'meta' => array_merge($event->meta, ['chunk_count' => $event->chunkCount]),
        ]);
    }
}
