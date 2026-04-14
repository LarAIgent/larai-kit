# Agents & Tools

LarAI Kit builds on [Laravel's AI SDK](https://laravel.com/blog/introducing-the-laravel-ai-sdk) agent system.

> **Namespace:** `LarAIgent\AiKit\...` (case-sensitive on Linux).

## Built-in Agents

### SupportAgent

A RAG-enabled agent that uses your knowledge base:

```php
use LarAIgent\AiKit\Agents\SupportAgent;

$response = (new SupportAgent())->prompt('How do I reset my password?');
echo $response;
```

### BookingAgent

An agent with tool-calling capability:

```php
use LarAIgent\AiKit\Agents\BookingAgent;

$response = (new BookingAgent())->prompt('Book a meeting with John tomorrow at 3pm');
```

## Using ChatService (recommended)

Instead of calling agents directly, use `ChatService` which handles RAG context injection, source citations, and multi-tenant scoping:

```php
use LarAIgent\AiKit\Services\Chat\ChatService;

$chat = app(ChatService::class);

// Simple
$result = $chat->sendMessage('What is the return policy?');
$result['reply'];   // AI response
$result['sources']; // [{name, url, type}]

// With custom agent and scope
$result = $chat->sendMessage(
    message: 'What products do you have?',
    agent: new ProductAgent(),
    scope: ['chatbot_id' => 42],
    topK: 10,
    threshold: 0.3,
);
```

### ChatService Parameters

| Parameter | Type | Default | Description |
|---|---|---|---|
| `message` | string | required | User's message |
| `agent` | Agent\|null | SupportAgent | Custom agent instance |
| `history` | iterable | `[]` | Previous conversation turns |
| `scope` | array | `[]` | Tenant scope for RAG filtering |
| `topK` | int\|null | config value (5) | Number of RAG chunks to retrieve |
| `threshold` | float\|null | config value (0.4) | Minimum similarity score |

## Creating Custom Agents

```bash
php artisan make:larai-agent ProductAgent
```

Creates `app/Ai/Agents/ProductAgent.php`:

```php
<?php

namespace App\Ai\Agents;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;
use Laravel\Ai\Tools\SimilaritySearch;
use LarAIgent\AiKit\Models\Document;

class ProductAgent implements Agent, HasTools
{
    use Promptable;

    public function instructions(): string
    {
        return 'You are a product expert. Answer questions using the knowledge base.';
    }

    public function tools(): iterable
    {
        return [
            SimilaritySearch::usingModel(Document::class, 'embedding'),
        ];
    }
}
```

## Creating Custom Tools

```bash
php artisan make:larai-tool CheckOrderTool
```

Creates `app/Ai/Tools/CheckOrderTool.php`:

```php
<?php

namespace App\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class CheckOrderTool implements Tool
{
    public function description(): Stringable|string
    {
        return 'Look up an order by order number.';
    }

    public function handle(Request $request): Stringable|string
    {
        $order = \App\Models\Order::where('number', $request['order_number'])->first();
        return $order ? "Order #{$order->number}: {$order->status}" : 'Order not found.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'order_number' => $schema->string()->required(),
        ];
    }
}
```
