[![Latest Version](https://img.shields.io/packagist/v/laraigent/larai-kit.svg)](https://packagist.org/packages/laraigent/larai-kit)
[![Total Downloads](https://img.shields.io/packagist/dt/laraigent/larai-kit.svg)](https://packagist.org/packages/laraigent/larai-kit)
[![License](https://img.shields.io/packagist/l/laraigent/larai-kit.svg)](LICENSE)

# LarAI Kit

**The missing AI toolkit for Laravel.** Add RAG, agents, document ingestion, and vector search to any Laravel app in under 5 minutes.

Built on top of [Laravel's first-party AI SDK](https://laravel.com/blog/introducing-the-laravel-ai-sdk). Works with **MySQL or PostgreSQL**. Supports **Pinecone and pgvector** for vector search.

> **Before you start — three things to know:**
>
> 1. The namespace is `LarAIgent\AiKit\...` (case-sensitive — matters on Linux)
> 2. Run `php artisan queue:work` in a separate terminal, or set `QUEUE_CONNECTION=sync` in `.env` (otherwise ingestion jobs queue silently)
> 3. OpenAI tier-1 keys allow ~3 RPM for embeddings — a 50KB document may rate-limit you. Set `LARAI_RETRY_MAX=5` or upgrade your OpenAI tier.

---

## Why LarAI Kit?

Laravel's AI SDK gives you the low-level building blocks (`Agent`, `Tool`, `prompt()`). But building a production RAG system on top of it requires a lot of plumbing:

- How do I parse PDFs and DOCX files into text?
- How do I chunk documents and generate embeddings?
- How do I store and search vectors?
- How do I inject relevant context into my agent's prompt?
- How do I make all this work with both Pinecone and pgvector?
- How do I make it not break when a service is unavailable?

**LarAI Kit solves all of this.** One `composer require`, a few `.env` keys, and you have a working RAG pipeline.

---

## 30-Second Quickstart

```bash
# 1. Install
composer require laraigent/larai-kit

# 2. Publish config + run migrations
php artisan larai:install

# 3. Add your keys to .env
OPENAI_API_KEY=sk-your-key
PINECONE_API_KEY=pcsk_your-key
PINECONE_INDEX_HOST=https://your-index.svc.pinecone.io

# 4. Verify
php artisan larai:doctor
```

That's it. You now have a RAG-enabled AI agent in your Laravel app.

---

## What You Get

| Feature | Description |
|---|---|
| **Agents + Tools** | Pre-built `SupportAgent` (RAG) and `BookingAgent` (tools) + scaffolding commands |
| **Document Ingestion** | Upload text, PDF, DOCX -> auto parse -> chunk -> batch embed -> batch upsert |
| **Vector Search** | Pinecone (default) or pgvector — swappable via `.env`, with retry/backoff |
| **RAG Chat** | Retrieves relevant context, injects into prompts, returns source citations |
| **Multi-Tenant Scoping** | Scope ingestion and retrieval per tenant/chatbot — prevents data leaks |
| **Graceful Degradation** | App works at every tier — missing services disable features, never crash |
| **Multi-Provider** | OpenAI, Anthropic, Gemini — switch AI provider via `.env` |
| **Multi-Database** | MySQL (default) or PostgreSQL — migrations adapt automatically |
| **Batch Operations** | `embedMany()` and `upsertMany()` for 5-10x faster ingestion |
| **Pipeline Events** | `IngestionStateChanged` fired on every transition for monitoring |
| **Artisan Commands** | `larai:install`, `larai:doctor --deep`, `larai:chat`, `make:larai-agent`, `make:larai-tool` |

---

## Feature Tiers

LarAI Kit detects what services are available and enables features accordingly:

| Tier | What you need | What works |
|---|---|---|
| **0** | Nothing configured | App boots but AI features are disabled |
| **1** | `OPENAI_API_KEY` | Chat works (no RAG) |
| **2** | + `PINECONE_API_KEY` | Chat + RAG (semantic search over your documents) |
| **3** | + AWS S3 credentials | Chat + RAG + cloud file storage |

---

## Configuration

All settings are in `.env`. No code changes to switch providers.

### AI Provider

```env
LARAI_AI_PROVIDER=openai     # openai, anthropic, gemini
OPENAI_API_KEY=sk-...
# or
ANTHROPIC_API_KEY=sk-ant-...
# or
GEMINI_API_KEY=...
```

### Vector Store

```env
LARAI_VECTOR_STORE=pinecone  # pinecone, pgvector, none

# Pinecone (works with MySQL or PostgreSQL)
PINECONE_API_KEY=pcsk_...
PINECONE_INDEX_HOST=https://your-index.svc.pinecone.io

# pgvector (requires PostgreSQL + pgvector extension)
# Just set LARAI_VECTOR_STORE=pgvector - no extra keys needed
```

### Database

```env
DB_CONNECTION=mysql          # mysql or pgsql - both fully supported
```

### Full Config Reference

Run `php artisan vendor:publish --tag=larai-kit-config` to publish `config/larai-kit.php`, then see [Configuration Docs](docs/configuration.md).

---

## Usage

### Chat with RAG context

```php
use LarAIgent\AiKit\Services\Chat\ChatService;

$chat = app(ChatService::class);
$result = $chat->sendMessage('What is your return policy?');

$result['reply'];   // AI response with cited sources
$result['sources']; // [{name, url, type}, ...]
```

### Ingest documents

```php
use LarAIgent\AiKit\Services\Ingestion\IngestionService;

$ingestion = app(IngestionService::class);

// Ingest text
$asset = $ingestion->ingestText('Our return policy allows returns within 30 days...');

// Ingest a file (PDF, DOCX, TXT)
$asset = $ingestion->ingestFile($request->file('document'));
```

### Create a custom agent

```bash
php artisan make:larai-agent ProductAgent
```

```php
// app/Ai/Agents/ProductAgent.php
class ProductAgent implements Agent, HasTools
{
    use Promptable;

    public function instructions(): string
    {
        return 'You are a product expert. Use the knowledge base to answer questions.';
    }

    public function tools(): iterable
    {
        return [
            SimilaritySearch::usingModel(Document::class, 'embedding'),
        ];
    }
}
```

### Create a custom tool

```bash
php artisan make:larai-tool CheckOrderTool
```

### Check system health

```bash
php artisan larai:doctor
```

```
LarAIgent Health Check

  [OK]      Database (mysql)
  [OK]      AI Provider (openai)
  [OK]      Vector Store (Pinecone)
  [OK]      Storage (public)
  [OK]      Cache (file)
  [SKIP]    Redis - not configured
  [SKIP]    Queue (sync) - sync mode

Configuration:
  AI Provider:   openai
  Vector Store:  pinecone
  Database:      mysql
  Feature Tier:  2
  RAG:           enabled
```

### Interactive CLI chat

```bash
php artisan larai:chat
```

---

## Artisan Commands

| Command | Description |
|---|---|
| `php artisan larai:install` | Publish config, run migrations, create storage link |
| `php artisan larai:doctor` | Check health of all services |
| `php artisan larai:chat` | Interactive CLI chat with the SupportAgent |
| `php artisan make:larai-agent {Name}` | Scaffold a new Agent class |
| `php artisan make:larai-tool {Name}` | Scaffold a new Tool class |

---

## Architecture

```
Your Laravel App
    |
    +-- composer require laraigent/larai-kit
    |
    +-- .env (choose your stack)
    |     |
    |     +-- AI Provider:    openai / anthropic / gemini
    |     +-- Vector Store:   pinecone / pgvector / none
    |     +-- Database:       mysql / pgsql
    |     +-- Storage:        local / s3
    |
    +-- LarAI Kit auto-configures everything
          |
          +-- FeatureDetector (detects what's available)
          +-- EmbeddingProvider (generates vectors)
          +-- VectorStore (stores/searches vectors)
          +-- FileStorage (stores uploaded files)
          +-- IngestionService (parse -> chunk -> embed pipeline)
          +-- RetrievalService (semantic search)
          +-- ChatService (RAG-augmented conversations)
```

---

## Ingestion Pipeline

When you ingest a document, this happens automatically:

```
Upload/Text --> Validate --> Store file --> Parse to text --> Chunk (with overlap)
    --> Generate embeddings --> Upsert to vector store --> Done
```

Each step is a queued job (or runs synchronously when `QUEUE_CONNECTION=sync`):
- `ParseAssetJob` - extracts text from PDF, DOCX, or plain text
- `ChunkAssetJob` - splits into overlapping chunks (configurable size)
- `EmbedChunksJob` - generates embeddings and upserts to Pinecone/pgvector

State tracked in `ai_ingestions` table: `queued -> parsing -> chunking -> embedding -> indexed`

---

## Supported File Types

| Type | Extension | Parser |
|---|---|---|
| Plain text | .txt, .md, .csv | Built-in |
| PDF | .pdf | Requires `smalot/pdfparser` |
| Word | .docx | Requires `phpoffice/phpword` |

Install optional parsers:
```bash
composer require smalot/pdfparser phpoffice/phpword
```

---

## Requirements

- PHP 8.3+
- Laravel 12 or 13
- `laravel/ai` ^0.4
- MySQL 8+ or PostgreSQL 14+
- An AI provider API key (OpenAI, Anthropic, or Gemini)

---

## Documentation

| Page | Description |
|---|---|
| [Installation](docs/installation.md) | Step-by-step setup guide |
| [Configuration](docs/configuration.md) | All `.env` options and config reference |
| [Agents & Tools](docs/agents-and-tools.md) | Creating custom agents and tools |
| [Ingestion Pipeline](docs/ingestion-pipeline.md) | Document upload, parsing, chunking, embedding |
| [Vector Stores](docs/vector-stores.md) | Pinecone vs pgvector setup and switching |
| [Artisan Commands](docs/artisan-commands.md) | All available CLI commands |
| [Testing](docs/testing.md) | How to test with fakes |

---

## Contributing

Contributions are welcome! Please:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

---

## License

MIT License. See [LICENSE](LICENSE) for details.

---

## Credits

- Built on [Laravel AI SDK](https://laravel.com/blog/introducing-the-laravel-ai-sdk)
- Inspired by the gap between Python RAG frameworks and the Laravel ecosystem
