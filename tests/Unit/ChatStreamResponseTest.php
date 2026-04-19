<?php

namespace LarAIgent\AiKit\Tests\Unit;

use LarAIgent\AiKit\Services\Chat\ChatStreamResponse;
use PHPUnit\Framework\TestCase;

/**
 * Mimics StreamableAgentResponse: iterates fake events, exposes a `usage`
 * property after iteration completes. We don't depend on the real SDK class
 * because ChatStreamResponse consumes its stream via duck typing.
 */
class FakeStream implements \IteratorAggregate
{
    public ?object $usage = null;

    public function __construct(
        private array $events,
        private ?object $finalUsage,
    ) {}

    public function getIterator(): \Generator
    {
        foreach ($this->events as $event) {
            yield $event;
        }

        // StreamableAgentResponse populates ->usage after the stream closes.
        $this->usage = $this->finalUsage;
    }
}

class ChatStreamResponseTest extends TestCase
{
    public function test_it_passes_full_text_and_usage_to_oncomplete(): void
    {
        $textEvent = new class {
            public string $delta = 'Hello ';
        };
        $textEvent2 = new class {
            public string $delta = 'world';
        };
        $streamEnd = new class {
            public object $usage;
            public function __construct()
            {
                $this->usage = (object) ['promptTokens' => 12, 'completionTokens' => 7];
            }
        };

        $finalUsage = (object) ['promptTokens' => 12, 'completionTokens' => 7];
        $stream = new FakeStream([$textEvent, $textEvent2, $streamEnd], $finalUsage);

        $captured = ['text' => null, 'usage' => null];
        $response = new ChatStreamResponse(
            stream: $stream,
            sources: [],
            conversationId: null,
            onComplete: function (string $fullText, mixed $usage) use (&$captured) {
                $captured['text'] = $fullText;
                $captured['usage'] = $usage;
            },
        );

        // Drain the iterator
        iterator_to_array($response->getIterator(), preserve_keys: false);

        $this->assertSame('Hello world', $captured['text']);
        $this->assertIsObject($captured['usage']);
        $this->assertSame(12, $captured['usage']->promptTokens);
        $this->assertSame(7, $captured['usage']->completionTokens);
    }

    public function test_it_handles_streams_without_usage_gracefully(): void
    {
        $textEvent = new class {
            public string $delta = 'hi';
        };

        $stream = new FakeStream([$textEvent], finalUsage: null);

        $captured = null;
        $response = new ChatStreamResponse(
            stream: $stream,
            sources: [],
            conversationId: null,
            onComplete: function (string $fullText, mixed $usage) use (&$captured) {
                $captured = $usage;
            },
        );

        iterator_to_array($response->getIterator(), preserve_keys: false);

        $this->assertNull($captured);
    }
}
