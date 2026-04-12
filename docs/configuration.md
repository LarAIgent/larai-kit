# Configuration

All settings are managed via `.env`. No code changes needed to switch providers.

## AI Provider

```env
LARAI_AI_PROVIDER=openai     # openai, anthropic, gemini
```

| Provider | Required Key | Supported Models |
|---|---|---|
| `openai` | `OPENAI_API_KEY` | gpt-4o, gpt-4o-mini, etc. |
| `anthropic` | `ANTHROPIC_API_KEY` | claude-sonnet-4-20250514, etc. |
| `gemini` | `GEMINI_API_KEY` | gemini-2.0-flash, etc. |

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

No additional keys needed. The extension must be installed on your PostgreSQL server.

### None (disables RAG)

```env
LARAI_VECTOR_STORE=none
```

Chat still works, but without document context.

## Embedding

```env
LARAI_EMBEDDING_DIMENSIONS=1536    # Match your embedding model
LARAI_SIMILARITY_THRESHOLD=0.4     # Min score for search results (0-1)
LARAI_RAG_TOP_K=5                  # Number of chunks to retrieve
```

## Chunking

```env
LARAI_CHUNK_SIZE=512               # Words per chunk
LARAI_CHUNK_OVERLAP=50             # Overlap between chunks
```

## File Upload

```env
LARAI_MAX_FILE_MB=20               # Maximum upload size
LARAI_STORAGE_DISK=public          # public or s3
```

## Conversation

```env
LARAI_HISTORY_TURNS=10             # Chat turns to retain
```

## Full `.env` Example

```env
# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=myapp
DB_USERNAME=root
DB_PASSWORD=

# AI
LARAI_AI_PROVIDER=openai
OPENAI_API_KEY=sk-proj-...
LARAI_CHAT_MODEL=gpt-4o-mini
LARAI_EMBEDDING_MODEL=text-embedding-3-small

# Vector Store
LARAI_VECTOR_STORE=pinecone
PINECONE_API_KEY=pcsk_...
PINECONE_INDEX_HOST=https://my-index.svc.pinecone.io

# Tuning (all optional, shown with defaults)
LARAI_EMBEDDING_DIMENSIONS=1536
LARAI_SIMILARITY_THRESHOLD=0.4
LARAI_RAG_TOP_K=5
LARAI_CHUNK_SIZE=512
LARAI_CHUNK_OVERLAP=50
LARAI_MAX_FILE_MB=20
LARAI_HISTORY_TURNS=10
```

## Switching Providers

Change `.env`, then clear config:

```bash
php artisan config:clear
php artisan larai:doctor    # verify the change
```

No migration or code changes needed.
