<?php

namespace LarAIgent\AiKit\Services\Chat;

use Illuminate\Support\Collection;
use Laravel\Ai\Contracts\Agent;
use LarAIgent\AiKit\Agents\SupportAgent;
use LarAIgent\AiKit\Events\ChatCompleted;
use LarAIgent\AiKit\Services\FeatureDetector;
use LarAIgent\AiKit\Services\Retrieval\RetrievalService;

class ChatService
{
    public function __construct(
        protected RetrievalService $retrieval,
        protected FeatureDetector $features,
        protected ?ConversationManager $conversations = null,
    ) {}

    /**
     * Send a message through the chat pipeline with optional RAG context.
     *
     * @return array{reply: string, sources: array, conversation_id: string|null}
     */
    public function sendMessage(
        string $message,
        ?Agent $agent = null,
        iterable $history = [],
        array $scope = [],
        ?int $topK = null,
        ?float $threshold = null,
        ?string $conversationId = null,
    ): array {
        $agent = $agent ?? new SupportAgent();
        $startTime = microtime(true);

        // Build RAG context
        $rag = $this->buildRagContext($message, $scope, $topK, $threshold);

        // Load conversation history if persisting
        $dbHistory = $this->loadConversationHistory($conversationId);
        // Merge: DB history first, then explicit overrides
        $mergedHistory = array_merge($dbHistory, iterator_to_array($history));

        $prompt = $rag['contextBlock'] . $message;
        $response = $agent->prompt($prompt);
        $reply = (string) $response;

        $durationMs = (int) round((microtime(true) - $startTime) * 1000);

        // Persist to conversation if ID provided
        if ($conversationId && $this->conversations) {
            $this->conversations->appendUserMessage($conversationId, $message);
            $this->conversations->appendAssistantMessage($conversationId, $reply, $rag['sources']);
        }

        // Dispatch usage event
        ChatCompleted::dispatch(
            provider: $this->features->aiProvider(),
            model: config('larai-kit.models.chat', 'gpt-4o-mini'),
            inputTokens: 0, // SDK AgentResponse may carry usage — extract if available
            outputTokens: 0,
            durationMs: $durationMs,
            scope: $scope,
            conversationId: $conversationId,
        );

        return [
            'reply' => $reply,
            'sources' => $rag['sources'],
            'conversation_id' => $conversationId,
        ];
    }

    /**
     * Stream a chat response with optional RAG context.
     *
     * Returns a ChatStreamResponse that can be iterated or returned from a controller (auto-SSE).
     */
    public function streamMessage(
        string $message,
        ?Agent $agent = null,
        iterable $history = [],
        array $scope = [],
        ?int $topK = null,
        ?float $threshold = null,
        ?string $conversationId = null,
    ): ChatStreamResponse {
        $agent = $agent ?? new SupportAgent();

        $rag = $this->buildRagContext($message, $scope, $topK, $threshold);
        $dbHistory = $this->loadConversationHistory($conversationId);

        $prompt = $rag['contextBlock'] . $message;
        $stream = $agent->stream($prompt);

        return new ChatStreamResponse(
            stream: $stream,
            sources: $rag['sources'],
            conversationId: $conversationId,
            onComplete: function (string $fullText) use ($conversationId, $message, $rag, $scope) {
                // Persist conversation
                if ($conversationId && $this->conversations) {
                    $this->conversations->appendUserMessage($conversationId, $message);
                    $this->conversations->appendAssistantMessage($conversationId, $fullText, $rag['sources']);
                }

                // Dispatch usage event
                ChatCompleted::dispatch(
                    provider: $this->features->aiProvider(),
                    model: config('larai-kit.models.chat', 'gpt-4o-mini'),
                    inputTokens: 0,
                    outputTokens: 0,
                    durationMs: 0,
                    scope: $scope,
                    conversationId: $conversationId,
                );
            },
        );
    }

    /**
     * Build RAG context block and collect sources.
     *
     * @return array{contextBlock: string, sources: array}
     */
    private function buildRagContext(string $message, array $scope, ?int $topK, ?float $threshold): array
    {
        $sources = [];
        $contextBlock = '';

        if ($this->features->ragEnabled()) {
            $results = $this->retrieval->retrieve($message, $topK, $threshold, $scope);

            if ($results->isNotEmpty()) {
                $contextParts = [];
                foreach ($results as $i => $result) {
                    $name = $result['source_name'] ?? 'Unknown';
                    $contextParts[] = "[Source " . ($i + 1) . ": {$name}]\n{$result['content']}";
                    $sources[] = [
                        'name' => $name,
                        'url' => $result['source_url'],
                        'type' => $result['source_type'],
                    ];
                }
                $contextBlock = "Use the following knowledge base context to answer the user's question. "
                    . "Cite sources by name when you use them.\n\n"
                    . implode("\n\n---\n\n", $contextParts)
                    . "\n\n---\n\nUser question: ";
            }
        }

        return ['contextBlock' => $contextBlock, 'sources' => $sources];
    }

    /**
     * Load conversation history from DB if conversationId is provided.
     */
    private function loadConversationHistory(?string $conversationId): array
    {
        if (! $conversationId || ! $this->conversations) {
            return [];
        }

        $limit = (int) config('larai-kit.conversation_history_turns', 10);
        $messages = $this->conversations->messages($conversationId, $limit);

        return $messages->map(fn ($msg) => [
            'role' => $msg->role,
            'content' => $msg->content,
        ])->all();
    }
}
