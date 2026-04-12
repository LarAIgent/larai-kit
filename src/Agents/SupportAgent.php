<?php

namespace LarAIgent\AiKit\Agents;

use LarAIgent\AiKit\Models\Document;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;
use Laravel\Ai\Tools\SimilaritySearch;

class SupportAgent implements Agent, HasTools
{
    use Promptable;

    public function instructions(): string
    {
        return 'You are a helpful support agent for LarAIgent. Use the knowledge base when relevant and be concise.';
    }

    /**
     * @return array<int, \Laravel\Ai\Contracts\Tool>
     */
    public function tools(): iterable
    {
        return [
            SimilaritySearch::usingModel(Document::class, 'embedding')
                ->withDescription('Search the LarAIgent knowledge base for relevant context.'),
        ];
    }
}
