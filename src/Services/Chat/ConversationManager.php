<?php

namespace LarAIgent\AiKit\Services\Chat;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use LarAIgent\AiKit\Models\Conversation;
use LarAIgent\AiKit\Models\Message;

class ConversationManager
{
    public function create(array $scope = [], ?string $title = null, array $metadata = []): Conversation
    {
        return Conversation::create([
            'title' => $title,
            'scope' => ! empty($scope) ? $scope : null,
            'metadata' => ! empty($metadata) ? $metadata : null,
        ]);
    }

    public function find(string $id): ?Conversation
    {
        return Conversation::find($id);
    }

    public function findOrFail(string $id): Conversation
    {
        return Conversation::findOrFail($id);
    }

    public function list(array $scope = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Conversation::query()->latest();

        if (! empty($scope)) {
            $query->forScope($scope);
        }

        return $query->paginate($perPage);
    }

    public function delete(string $id): bool
    {
        return (bool) Conversation::where('id', $id)->delete();
    }

    /**
     * Get messages for a conversation, most recent N.
     */
    public function messages(string $conversationId, ?int $limit = null): Collection
    {
        $limit = $limit ?? (int) config('larai-kit.conversation_history_turns', 10);

        return Message::where('conversation_id', $conversationId)
            ->latest('id')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();
    }

    public function appendUserMessage(string $conversationId, string $content): Message
    {
        return Message::create([
            'conversation_id' => $conversationId,
            'role' => 'user',
            'content' => $content,
        ]);
    }

    public function appendAssistantMessage(
        string $conversationId,
        string $content,
        array $sources = [],
        ?int $inputTokens = null,
        ?int $outputTokens = null,
    ): Message {
        return Message::create([
            'conversation_id' => $conversationId,
            'role' => 'assistant',
            'content' => $content,
            'sources' => ! empty($sources) ? $sources : null,
            'input_tokens' => $inputTokens,
            'output_tokens' => $outputTokens,
        ]);
    }
}
