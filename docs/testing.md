# Testing

## Agent Fakes

Use Laravel AI SDK's `fake()` method to test without calling real APIs:

```php
use LarAIgent\AiKit\Agents\SupportAgent;

SupportAgent::fake(['This is a test response.']);

$response = (new SupportAgent())->prompt('Hello');
$this->assertEquals('This is a test response.', (string) $response);
```

## Testing the ChatService

```php
use LarAIgent\AiKit\Agents\SupportAgent;
use LarAIgent\AiKit\Services\Chat\ChatService;

SupportAgent::fake(['Mocked reply.']);

$chat = app(ChatService::class);
$result = $chat->sendMessage('Hello');

$this->assertEquals('Mocked reply.', $result['reply']);
```

## Testing with NullVectorStore

When `LARAI_VECTOR_STORE=none` in your test environment, the `NullVectorStore` is used automatically. RAG features return empty results gracefully.

In `phpunit.xml`:
```xml
<env name="LARAI_VECTOR_STORE" value="none"/>
<env name="LARAI_AI_PROVIDER" value="openai"/>
```

## FeatureDetector in Tests

```php
use LarAIgent\AiKit\Services\FeatureDetector;

$detector = app(FeatureDetector::class);

config(['larai-kit.vector_store' => 'none']);
$this->assertFalse($detector->ragEnabled());

config(['larai-kit.ai_provider' => 'openai']);
$this->assertEquals('openai', $detector->aiProvider());
```

## Chunker Unit Test

```php
use LarAIgent\AiKit\Services\Ingestion\Chunker;

$chunker = new Chunker(chunkSize: 20, overlap: 5);
$chunks = $chunker->chunk('Your long document text here...');

$this->assertNotEmpty($chunks);
$this->assertEquals(0, $chunks[0]['chunk_index']);
```
