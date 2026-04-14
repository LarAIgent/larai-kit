# Vector Stores

LarAI Kit supports multiple vector store backends. Switch between them via `.env` — no code changes.

## Choosing a Vector Store

| Store | Best for | Requires |
|---|---|---|
| **Pinecone** (default) | Most users, any database, managed service | Pinecone account (free tier available) |
| **pgvector** | Self-hosted, no external services | PostgreSQL + pgvector extension |
| **none** | Chat-only mode, no RAG | Nothing |

## Pinecone

Works with **MySQL or PostgreSQL**. Vectors stored in Pinecone's cloud.

### Setup

1. Create account at [pinecone.io](https://pinecone.io)
2. Create an index: Dimensions `1536`, Metric `cosine`
3. Add to `.env`:

```env
LARAI_VECTOR_STORE=pinecone
PINECONE_API_KEY=pcsk_your-key
PINECONE_INDEX_HOST=https://your-index-abc123.svc.pinecone.io
```

### Features

- **Batch upsert** — up to 100 vectors per API call via `upsertMany()`
- **Retry with backoff** — automatic retry on 429/5xx with exponential backoff and jitter
- **Scoped search** — scope parameters translate to Pinecone metadata `$eq` filters for multi-tenant isolation
- **Zero dependencies** — uses Laravel's built-in `Http` facade

### Free tier

100K vectors, 1 index — enough for development and small production apps.

## pgvector

Self-hosted vector search using PostgreSQL's pgvector extension.

```env
DB_CONNECTION=pgsql
LARAI_VECTOR_STORE=pgvector
```

### Scoped search

Scope parameters translate to `WHERE source_meta->key = value` JSON queries.

## None (disable RAG)

```env
LARAI_VECTOR_STORE=none
```

Chat still works but without document context. The `NullVectorStore` silently no-ops all operations.

## Multi-Tenant Scoping

Both Pinecone and pgvector support scoped queries to prevent data leaks between tenants:

```php
// Ingest with scope
$ingestion->ingestText($content, scope: ['chatbot_id' => 42]);

// Retrieve with same scope — only returns that tenant's documents
$retrieval->retrieve($query, scope: ['chatbot_id' => 42]);

// ChatService with scope
$chat->sendMessage($message, scope: ['chatbot_id' => 42]);
```

**Without scope, queries are global.** Always pass scope in multi-tenant applications.

## Custom Vector Store

Implement the `VectorStore` contract:

```php
use LarAIgent\AiKit\Contracts\VectorStore;
use Illuminate\Support\Collection;

class WeaviateVectorStore implements VectorStore
{
    public function upsert(int $chunkId, array $embedding, array $metadata = []): void { ... }
    public function upsertMany(array $items): void { ... }
    public function search(array $embedding, int $limit = 5, float $threshold = 0.4, array $scope = []): Collection { ... }
    public function delete(array $chunkIds): void { ... }
}
```

Bind in a service provider:

```php
$this->app->singleton(VectorStore::class, WeaviateVectorStore::class);
```

## Batch Operations

Both `upsertMany()` and `embedMany()` support batched operations:

```php
// Embedding: batch all texts in one API call
$embedder->embedMany(['text 1', 'text 2', 'text 3']);

// Vector store: batch upsert (Pinecone does 100/request)
$store->upsertMany([
    ['chunk_id' => 1, 'embedding' => [...], 'metadata' => [...]],
    ['chunk_id' => 2, 'embedding' => [...], 'metadata' => [...]],
]);
```

The ingestion pipeline uses both automatically — you don't need to call these directly.
