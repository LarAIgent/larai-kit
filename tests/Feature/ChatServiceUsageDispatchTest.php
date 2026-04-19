<?php

namespace LarAIgent\AiKit\Tests\Feature;

use Illuminate\Support\Facades\Event;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Responses\AgentResponse;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\Data\Usage;
use LarAIgent\AiKit\Events\ChatCompleted;
use LarAIgent\AiKit\Services\Chat\ChatService;
use LarAIgent\AiKit\Services\FeatureDetector;
use LarAIgent\AiKit\Services\Retrieval\RetrievalService;
use LarAIgent\AiKit\Tests\TestCase;
use Mockery;

/**
 * Verifies that ChatService::sendMessage() extracts token counts from the
 * AgentResponse's `usage` property and dispatches them on ChatCompleted.
 */
class ChatServiceUsageDispatchTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_dispatches_chat_completed_with_real_usage(): void
    {
        Event::fake([ChatCompleted::class]);

        $agent = Mockery::mock(Agent::class);
        $agent->shouldReceive('prompt')
            ->once()
            ->andReturn(new AgentResponse(
                invocationId: 'test-invocation',
                text: 'hi there',
                usage: new Usage(promptTokens: 42, completionTokens: 9),
                meta: new Meta('openai', 'gpt-4o-mini'),
            ));

        $service = $this->makeChatService();
        $result = $service->sendMessage('hello', agent: $agent);

        $this->assertSame('hi there', $result['reply']);
        Event::assertDispatched(ChatCompleted::class, function (ChatCompleted $event) {
            return $event->inputTokens === 42
                && $event->outputTokens === 9
                && $event->provider === 'openai'
                && $event->durationMs >= 0;
        });
    }

    public function test_it_dispatches_zero_tokens_when_usage_is_empty(): void
    {
        Event::fake([ChatCompleted::class]);

        // Real AgentResponse but with zeroed Usage — happens if the provider
        // doesn't return token counts or the SDK can't parse them.
        $agent = Mockery::mock(Agent::class);
        $agent->shouldReceive('prompt')
            ->once()
            ->andReturn(new AgentResponse(
                invocationId: 'test-invocation',
                text: 'hi',
                usage: new Usage(),
                meta: new Meta('openai', 'gpt-4o-mini'),
            ));

        $service = $this->makeChatService();
        $service->sendMessage('hello', agent: $agent);

        Event::assertDispatched(ChatCompleted::class, function (ChatCompleted $event) {
            return $event->inputTokens === 0 && $event->outputTokens === 0;
        });
    }

    private function makeChatService(): ChatService
    {
        $retrieval = Mockery::mock(RetrievalService::class);
        $features = Mockery::mock(FeatureDetector::class);
        $features->shouldReceive('aiProvider')->andReturn('openai');
        $features->shouldReceive('ragEnabled')->andReturn(false);

        return new ChatService($retrieval, $features);
    }
}
