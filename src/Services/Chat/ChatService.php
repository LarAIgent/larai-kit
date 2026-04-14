<?php

namespace LarAIgent\AiKit\Services\Chat;

use Laravel\Ai\Contracts\Agent;
use LarAIgent\AiKit\Agents\SupportAgent;
use LarAIgent\AiKit\Services\FeatureDetector;
use LarAIgent\AiKit\Services\Retrieval\RetrievalService;

class ChatService
{
    public function __construct(
        protected RetrievalService $retrieval,
        protected FeatureDetector $features,
    ) {}

    /**
     * Send a message through the chat pipeline with optional RAG context.
     *
     * @param string $message          The user's message
     * @param Agent|null $agent        Custom agent (defaults to SupportAgent)
     * @param iterable $history        Previous conversation turns
     * @param array $scope             Tenant scope for RAG filtering (e.g. ['chatbot_id' => 42])
     * @param int|null $topK           Override RAG result count
     * @param float|null $threshold    Override similarity threshold
     * @return array{reply: string, sources: array}
     */
    public function sendMessage(
        string $message,
        ?Agent $agent = null,
        iterable $history = [],
        array $scope = [],
        ?int $topK = null,
        ?float $threshold = null,
    ): array {
        $agent = $agent ?? new SupportAgent();
        $sources = [];
        $contextBlock = '';

        // If RAG is enabled, retrieve relevant chunks with scope
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

        $prompt = $contextBlock . $message;
        $response = $agent->prompt($prompt);

        return [
            'reply' => (string) $response,
            'sources' => $sources,
        ];
    }
}
