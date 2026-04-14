# Configuration

All settings are managed via `.env`. No code changes needed to switch providers.

## AI Provider

```env
LARAI_AI_PROVIDER=openai     # openai, anthropic, gemini
```

| Provider | Required Key | Models |
|---|---|---|
| `openai` | `OPENAI_API_KEY` | gpt-4o, gpt-4o-mini |
| `anthropic` | `ANTHROPIC_API_KEY` | claude-sonnet-4-20250514 |
| `gemini` | `GEMINI_API_KEY` | gemini-2.0-flash |

```env
LARAI_CHAT_MODEL=gpt-4o-mini
LARAI_EMBEDDING_MODEL=text-embedding-3-small
```

## Vector Store

```env
LARAI_VECTOR_STORE=pinecone  # pinecone, pgvector, none
```

### Pinecone (default, works with any database)

```env
PINECONE_API_KEY=pcsk_your-key
PINECONE_INDEX_HOST=https://your-index.svc.pinecone.io
```

### pgvector (requires PostgreSQL)

```env
DB_CONNECTION=pgsql
LARAI_VECTOR_STORE=pgvector
```

### None (disables RAG)

```env
LARAI_VECTOR_STORE=none
```

## Embedding

```env
LARAI_EMBEDDING_DIMENSIONS=1536
LARAI_SIMILARITY_THRESHOLD=0.4
LARAI_RAG_TOP_K=5
```

## Chunking

```env
LARAI_CHUNK_SIZE=512
LARAI_CHUNK_OVERLAP=50
```

## File Upload

```env
LARAI_MAX_FILE_MB=20
LARAI_STORAGE_DISK=public    # public or s3
```

## Retry / Backoff

Controls retry behavior for rate-limited (429) and server error (5xx) API responses from Pinecone:

```env
LARAI_RETRY_MAX=3            # Max retry attempts
LARAI_RETRY_DELAY_MS=1000    # Base delay in ms (doubles each attempt with jitter)
```

## Conversation

```env
LARAI_HISTORY_TURNS=10
```

## Full `.env` Example

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=myapp
DB_USERNAME=root
DB_PASSWORD=

LARAI_AI_PROVIDER=openai
OPENAI_API_KEY=sk-proj-...
LARAI_CHAT_MODEL=gpt-4o-mini
LARAI_EMBEDDING_MODEL=text-embedding-3-small

LARAI_VECTOR_STORE=pinecone
PINECONE_API_KEY=pcsk_...
PINECONE_INDEX_HOST=https://my-index.svc.pinecone.io

# All optional — shown with defaults
LARAI_EMBEDDING_DIMENSIONS=1536
LARAI_SIMILARITY_THRESHOLD=0.4
LARAI_RAG_TOP_K=5
LARAI_CHUNK_SIZE=512
LARAI_CHUNK_OVERLAP=50
LARAI_MAX_FILE_MB=20
LARAI_HISTORY_TURNS=10
LARAI_RETRY_MAX=3
LARAI_RETRY_DELAY_MS=1000
```

## Switching Providers

Change `.env`, then clear config:

```bash
php artisan config:clear
php artisan larai:doctor
```
