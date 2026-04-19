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
        [$inputTokens, $outputTokens] = $this->extractUsage($response);

        // Persist to conversation if ID provided
        if ($conversationId && $this->conversations) {
            $this->conversations->appendUserMessage($conversationId, $message);
            $this->conversations->appendAssistantMessage($conversationId, $reply, $rag['sources']);
        }

        ChatCompleted::dispatch(
            provider: $this->features->aiProvider(),
            model: config('larai-kit.models.chat', 'gpt-4o-mini'),
            inputTokens: $inputTokens,
            outputTokens: $outputTokens,
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
        $startTime = microtime(true);

        $rag = $this->buildRagContext($message, $scope, $topK, $threshold);
        $dbHistory = $this->loadConversationHistory($conversationId);

        $prompt = $rag['contextBlock'] . $message;
        $stream = $agent->stream($prompt);

        return new ChatStreamResponse(
            stream: $stream,
            sources: $rag['sources'],
            conversationId: $conversationId,
            onComplete: function (string $fullText, mixed $usage) use ($conversationId, $message, $rag, $scope, $startTime) {
                if ($conversationId && $this->conversations) {
                    $this->conversations->appendUserMessage($conversationId, $message);
                    $this->conversations->appendAssistantMessage($conversationId, $fullText, $rag['sources']);
                }

                [$inputTokens, $outputTokens] = $this->extractUsageFromObject($usage);
                $durationMs = (int) round((microtime(true) - $startTime) * 1000);

                ChatCompleted::dispatch(
                    provider: $this->features->aiProvider(),
                    model: config('larai-kit.models.chat', 'gpt-4o-mini'),
                    inputTokens: $inputTokens,
                    outputTokens: $outputTokens,
                    durationMs: $durationMs,
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

    /**
     * Extract [$promptTokens, $completionTokens] from an AgentResponse-like
     * object. Uses duck typing so test doubles and future SDK shape changes
     * don't break this path — we only care about the public `usage` property
     * and its `promptTokens` / `completionTokens` integers.
     *
     * @return array{0: int, 1: int}
     */
    private function extractUsage(mixed $response): array
    {
        if (! is_object($response)) {
            return [0, 0];
        }

        $usage = $response->usage ?? null;
        return $this->extractUsageFromObject($usage);
    }

    /**
     * Extract [$promptTokens, $completionTokens] from a `Usage` data object.
     *
     * @return array{0: int, 1: int}
     */
    private function extractUsageFromObject(mixed $usage): array
    {
        if (! is_object($usage)) {
            return [0, 0];
        }

        return [
            (int) ($usage->promptTokens ?? 0),
            (int) ($usage->completionTokens ?? 0),
        ];
    }
}
