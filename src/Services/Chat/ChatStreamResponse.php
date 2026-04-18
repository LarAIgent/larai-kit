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
            // Laravel AI SDK yields different StreamEvent types (TextDelta,
            // StreamEnd, tool use events, etc.). Only emit text for events
            // that actually carry a text delta — everything else is metadata
            // that must not be serialized into the output stream.
            $text = $this->extractDelta($event);

            if ($text === null || $text === '') {
                continue;
            }

            $this->fullText .= $text;
            yield ['type' => 'text_delta', 'delta' => $text];
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

    /**
     * Extract text content from a Laravel AI SDK stream event.
     *
     * Only TextDelta events carry a text fragment; StreamEnd and tool events
     * return null (they're metadata, not visible output). Uses duck typing
     * on a `delta` property so we don't depend on the SDK's exact class names.
     */
    protected function extractDelta(mixed $event): ?string
    {
        // Plain strings: yield as-is
        if (is_string($event)) {
            return $event;
        }

        if (! is_object($event)) {
            return null;
        }

        // TextDelta has a public ->delta string property
        if (property_exists($event, 'delta')) {
            $delta = $event->delta;
            if (is_string($delta)) {
                return $delta;
            }
            // Nested delta object (some SDK shapes)
            if (is_object($delta) && property_exists($delta, 'text') && is_string($delta->text)) {
                return $delta->text;
            }
        }

        // Fallback: some SDK events expose text() / getText()
        if (method_exists($event, 'text')) {
            $value = $event->text();
            return is_string($value) ? $value : null;
        }

        // Any other event type (StreamEnd, ToolUse, etc.) — skip, not text output
        return null;
    }
}
