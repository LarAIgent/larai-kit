# Agents & Tools

LarAI Kit builds on [Laravel's AI SDK](https://laravel.com/blog/introducing-the-laravel-ai-sdk) agent system.

## Built-in Agents

### SupportAgent

A RAG-enabled agent that uses your knowledge base to answer questions:

```php
use LarAIgent\AiKit\Agents\SupportAgent;

$response = (new SupportAgent())->prompt('How do I reset my password?');
echo $response; // Answer with context from your documents
```

### BookingAgent

An agent with tool-calling capability:

```php
use LarAIgent\AiKit\Agents\BookingAgent;

$response = (new BookingAgent())->prompt('Book a meeting with John tomorrow at 3pm');
echo $response; // Uses BookAppointmentTool
```

## Creating Custom Agents

### Scaffold

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

class ProductAgent implements Agent, HasTools
{
    use Promptable;

    public function instructions(): string
    {
        return 'You are a product expert for our e-commerce store.';
    }

    public function tools(): iterable
    {
        return [
            // Add tools here
        ];
    }
}
```

### Adding RAG to your agent

```php
use LarAIgent\AiKit\Models\Document;
use Laravel\Ai\Tools\SimilaritySearch;

public function tools(): iterable
{
    return [
        SimilaritySearch::usingModel(Document::class, 'embedding')
            ->withDescription('Search the product knowledge base.'),
    ];
}
```

### Using the ChatService (recommended for RAG)

Instead of calling agents directly, use `ChatService` which handles RAG context injection automatically:

```php
use LarAIgent\AiKit\Services\Chat\ChatService;

$chat = app(ChatService::class);
$result = $chat->sendMessage('What products do you have?');

$result['reply'];   // AI response
$result['sources']; // Sources used
```

## Creating Custom Tools

### Scaffold

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
        $order = Order::where('number', $request['order_number'])->first();

        if (! $order) {
            return 'Order not found.';
        }

        return "Order #{$order->number}: {$order->status}, placed on {$order->created_at->format('M j, Y')}";
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'order_number' => $schema->string()->required(),
        ];
    }
}
```

### Attach tools to agents

```php
public function tools(): iterable
{
    return [
        new \App\Ai\Tools\CheckOrderTool(),
        SimilaritySearch::usingModel(Document::class, 'embedding'),
    ];
}
```
