<?php

namespace LarAIgent\AiKit\Contracts;

interface ChatProvider
{
    /**
     * Send a message with optional context and conversation history.
     *
     * @param string $message User message
     * @param array<int, array{role: string, content: string}> $history Previous turns
     * @param array<int, array{content: string, source_name: string, source_url: string|null}> $context RAG chunks
     * @return array{reply: string, sources: array}
     */
    public function send(string $message, array $history = [], array $context = []): array;
}
