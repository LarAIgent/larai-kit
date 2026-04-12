<?php

namespace LarAIgent\AiKit\Services\Embedding;

use Illuminate\Support\Str;
use LarAIgent\AiKit\Contracts\EmbeddingProvider;

class OpenAiEmbedding implements EmbeddingProvider
{
    public function embed(string $text): array
    {
        return Str::of($text)->toEmbeddings()->toArray();
    }

    public function dimensions(): int
    {
        return (int) config('larai-kit.embedding_dimensions', 1536);
    }
}
