<?php

namespace LarAIgent\AiKit\Services\Chat;

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
     * @return array{reply: string, sources: array}
     */
    public function sendMessage(string $message): array
    {
        $sources = [];
        $contextBlock = '';

        // If RAG is enabled, retrieve relevant chunks and build context
        if ($this->features->ragEnabled()) {
            $results = $this->retrieval->retrieve($message);

            if ($results->isNotEmpty()) {
                $contextParts = [];
                foreach ($results as $i => $result) {
                    $contextParts[] = "[Source " . ($i + 1) . ": {$result['source_name']}]\n{$result['content']}";
                    $sources[] = [
                        'name' => $result['source_name'],
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

        $response = (new SupportAgent())->prompt($prompt);

        return [
            'reply' => (string) $response,
            'sources' => $sources,
        ];
    }
}
