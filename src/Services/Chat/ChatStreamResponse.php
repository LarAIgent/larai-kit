<?php

namespace LarAIgent\AiKit\Services\Chat;

use Closure;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChatStreamResponse implements \IteratorAggregate, Responsable
{
    protected string $fullText = '';

    public function __construct(
        protected mixed $stream,
        protected array $sources = [],
        protected ?string $conversationId = null,
        protected ?Closure $onComplete = null,
    ) {}

    public function getIterator(): \Traversable
    {
        $this->fullText = '';

        foreach ($this->stream as $event) {
            // Laravel AI SDK yields StreamEvent objects with different types
            if (is_object($event)) {
                $text = method_exists($event, 'text') ? $event->text() : (string) $event;
            } else {
                $text = (string) $event;
            }

            if ($text !== '') {
                $this->fullText .= $text;
                yield ['type' => 'text_delta', 'delta' => $text];
            }
        }

        // Yield sources as final event
        if (! empty($this->sources)) {
            yield [
                'type' => 'sources',
                'sources' => $this->sources,
                'conversation_id' => $this->conversationId,
            ];
        }

        // Done event
        yield ['type' => 'done'];

        // Fire completion callback (persist conversation, dispatch events)
        if ($this->onComplete) {
            ($this->onComplete)($this->fullText);
        }
    }

    /**
     * Auto-convert to SSE response when returned from a controller.
     */
    public function toResponse($request): StreamedResponse
    {
        return new StreamedResponse(function () {
            foreach ($this as $event) {
                echo "data: " . json_encode($event) . "\n\n";

                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    public function text(): string
    {
        return $this->fullText;
    }

    public function sources(): array
    {
        return $this->sources;
    }

    public function conversationId(): ?string
    {
        return $this->conversationId;
    }
}
