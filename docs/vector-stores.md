# Vector Stores

LarAI Kit supports multiple vector store backends. Switch between them via `.env` - no code changes.

## Choosing a Vector Store

| Store | Best for | Requires |
|---|---|---|
| **Pinecone** (default) | Most users, any database, managed service | Pinecone account (free tier available) |
| **pgvector** | Self-hosted, no external services | PostgreSQL + pgvector extension |
| **none** | Chat-only mode, no RAG | Nothing |

## Pinecone

Works with **MySQL or PostgreSQL**. Vectors stored in Pinecone's cloud, document metadata stored in your database.

### Setup

1. Create account at [pinecone.io](https://pinecone.io)
2. Create an index:
   - Dimensions: `1536`
   - Metric: `cosine`
3. Add to `.env`:

```env
LARAI_VECTOR_STORE=pinecone
PINECONE_API_KEY=pcsk_your-key
PINECONE_INDEX_HOST=https://your-index-abc123.svc.pinecone.io
```

### How it works

- Embeddings are generated via OpenAI and sent to Pinecone
- Search queries are embedded and matched against Pinecone's index
- Document metadata (source name, URL, content preview) stored as Pinecone metadata
- No extra composer packages needed - uses Laravel's HTTP client

### Free tier limits

- 100K vectors
- 1 index
- Enough for most development and small production apps

## pgvector

Self-hosted vector search using PostgreSQL's pgvector extension. No external services.

### Setup

```env
DB_CONNECTION=pgsql
LARAI_VECTOR_STORE=pgvector
```

Requires:
- PostgreSQL 14+
- pgvector extension installed

Linux:
```bash
sudo apt install postgresql-17-pgvector
```

Docker:
```bash
docker run -d -p 5432:5432 -e POSTGRES_PASSWORD=secret pgvector/pgvector:pg17
```

### How it works

- Embeddings stored directly in `ai_documents.embedding` column (vector type)
- Search uses `whereVectorSimilarTo()` from Laravel's query builder
- Everything in one database - no external service dependency

## None (disable RAG)

```env
LARAI_VECTOR_STORE=none
```

Chat still works but without document context. Ingestion is disabled. The `NullVectorStore` silently no-ops all operations.

## Switching Between Stores

```bash
# Edit .env: LARAI_VECTOR_STORE=pgvector
php artisan config:clear
php artisan larai:doctor
```

Note: Vectors in Pinecone won't be in pgvector and vice versa. If switching, you'll need to re-ingest your documents.

## Custom Vector Store

Implement the `VectorStore` contract:

```php
use LarAIgent\AiKit\Contracts\VectorStore;

class MyCustomStore implements VectorStore
{
    public function upsert(int $chunkId, array $embedding, array $metadata = []): void { ... }
    public function search(array $embedding, int $limit = 5, float $threshold = 0.4): Collection { ... }
    public function delete(array $chunkIds): void { ... }
}
```

Bind it in a service provider:

```php
$this->app->singleton(VectorStore::class, MyCustomStore::class);
```
