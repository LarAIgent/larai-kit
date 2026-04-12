# Installation

## Requirements

- PHP 8.3+
- Laravel 12 or 13
- MySQL 8+ or PostgreSQL 14+
- An AI provider API key (OpenAI, Anthropic, or Gemini)
- Pinecone account (free tier works) if using Pinecone vector store

## Step 1: Install the package

```bash
composer require laraigent/larai-kit
```

## Step 2: Run the install command

```bash
php artisan larai:install
```

This does three things:
1. Publishes `config/larai-kit.php`
2. Runs database migrations (creates `ai_documents`, `ai_assets`, `ai_chunks`, `ai_ingestions` tables)
3. Creates the storage symlink for local file uploads

## Step 3: Configure `.env`

Add your API keys:

```env
# Required: AI provider
LARAI_AI_PROVIDER=openai
OPENAI_API_KEY=sk-your-key-here

# Recommended: Vector store for RAG
LARAI_VECTOR_STORE=pinecone
PINECONE_API_KEY=pcsk_your-key
PINECONE_INDEX_HOST=https://your-index-abc123.svc.pinecone.io
```

## Step 4: Verify

```bash
php artisan larai:doctor
```

You should see green `[OK]` for Database, AI Provider, and Vector Store.

## Optional: PDF and DOCX support

```bash
composer require smalot/pdfparser phpoffice/phpword
```

## Pinecone Setup

1. Sign up at [pinecone.io](https://pinecone.io) (free tier: 100K vectors)
2. Create an index:
   - **Dimensions**: `1536` (matches OpenAI text-embedding-3-small)
   - **Metric**: `cosine`
3. Copy the API key from your project settings
4. Copy the index host URL (looks like `https://your-index-abc123.svc.aped-1234.pinecone.io`)
5. Add both to `.env`

## pgvector Setup (alternative)

If you prefer self-hosted vector search:

```env
DB_CONNECTION=pgsql
LARAI_VECTOR_STORE=pgvector
```

Requires PostgreSQL with the pgvector extension installed. On Linux:
```bash
sudo apt install postgresql-17-pgvector
```

## Next Steps

- [Configuration](configuration.md) - All available settings
- [Agents & Tools](agents-and-tools.md) - Build your first agent
- [Ingestion Pipeline](ingestion-pipeline.md) - Upload documents
