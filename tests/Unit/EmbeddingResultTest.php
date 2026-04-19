<?php

namespace LarAIgent\AiKit\Tests\Unit;

use LarAIgent\AiKit\Services\Embedding\EmbeddingResult;
use PHPUnit\Framework\TestCase;

class EmbeddingResultTest extends TestCase
{
    public function test_it_exposes_vectors_and_tokens(): void
    {
        $result = new EmbeddingResult(
            vectors: [[0.1, 0.2], [0.3, 0.4]],
            tokens: 42,
        );

        $this->assertSame([[0.1, 0.2], [0.3, 0.4]], $result->vectors);
        $this->assertSame(42, $result->tokens);
    }

    public function test_empty_result_is_valid(): void
    {
        $result = new EmbeddingResult(vectors: [], tokens: 0);

        $this->assertSame([], $result->vectors);
        $this->assertSame(0, $result->tokens);
    }
}
