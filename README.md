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
| **Document Ingestion** | Upload text, PDF, DOCX, or URL -> auto parse -> chunk -> batch embed -> batch upsert |
| **URL Ingestion** | `ingestUrl()` with SSRF protection — fetches, parses HTML/text, chunks, embeds |
| **Vector Search** | Pinecone (default) or pgvector — swappable via `.env`, with retry/backoff |
| **RAG Chat** | Retrieves relevant context, injects into prompts, returns source citations |
| **Streaming Chat** | `streamMessage()` returns SSE-ready response — token-by-token with sources at the end |
| **Conversations** | Persistent chat threads with `Conversation` + `Message` models (UUID, scope-aware) |
| **Multi-Tenant Scoping** | Scope ingestion, retrieval, and conversations per tenant — prevents data leaks |
| **Usage Tracking** | `ChatCompleted` + `EmbeddingsCompleted` events, opt-in DB persistence via `ai_usage` |
| **Graceful Degradation** | App works at every tier — missing services disable features, never crash |
| **Multi-Provider** | OpenAI, Anthropic, Gemini — switch AI provider via `.env` |
| **Multi-Database** | MySQL (default) or PostgreSQL — migrations adapt automatically |
| **Batch Operations** | `embedMany()` and `upsertMany()` for 5-10x faster ingestion |
| **Health Endpoint** | JSON web endpoint at `/_larai/health` + `larai:doctor --deep` CLI |
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

### Conversations (persistent chat threads)

```php
use LarAIgent\AiKit\Services\Chat\ConversationManager;

$conversations = app(ConversationManager::class);
$chat = app(ChatService::class);

// Create a conversation
$convo = $conversations->create(scope: ['chatbot_id' => 42], title: 'Support thread');

// Send messages — history auto-loaded and persisted
$result = $chat->sendMessage('What is the return policy?', conversationId: $convo->id);
$result = $chat->sendMessage('How long do I have?', conversationId: $convo->id);
// Both messages + replies are stored in ai_messages
```

### Streaming chat (SSE)

```php
// In a controller — return directly for auto-SSE response
public function stream(Request $request)
{
    $chat = app(ChatService::class);
    return $chat->streamMessage($request->input('message'));
    // Returns: data: {"type":"text_delta","delta":"..."} per chunk
    //          data: {"type":"sources","sources":[...]}
    //          data: {"type":"done"}
}
```

### Ingest documents

```php
use LarAIgent\AiKit\Services\Ingestion\IngestionService;

$ingestion = app(IngestionService::class);

// Ingest text
$asset = $ingestion->ingestText('Our return policy allows returns within 30 days...');

// Ingest a file (PDF, DOCX, TXT)
$asset = $ingestion->ingestFile($request->file('document'));

// Ingest a URL (with SSRF protection)
$asset = $ingestion->ingestUrl('https://example.com/docs/faq');

// With multi-tenant scope
$asset = $ingestion->ingestUrl('https://example.com/docs', scope: ['chatbot_id' => 42]);
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
php artisan larai:doctor        # config checks
php artisan larai:doctor --deep  # + live API probe (embedding test)
```

```
LarAI Kit Health Check

  [OK]      Database (mysql, 3.2ms)
  [OK]      AI Provider (openai)
  [OK]      Embedding probe (1536 dims, 2841ms)    <-- --deep only
  [OK]      Vector Store (pinecone)
  [OK]      Storage (public)
  [OK]      Cache (file)
  [SKIP]    Redis — not configured
  [SKIP]    Queue — sync mode

Configuration:
  AI Provider:   openai
  Vector Store:  pinecone
  Database:      mysql
  Feature Tier:  2
  RAG:           enabled
```

### Web health endpoint (JSON)

Enable in `.env`:
```env
LARAI_HEALTH_ENABLED=true
LARAI_HEALTH_PATH=_larai/health
```

```bash
curl http://localhost/_larai/health?deep=true
```

Returns structured JSON with all check results — wire into monitoring dashboards.

### Usage tracking

```env
LARAI_TRACK_USAGE=true   # Records to ai_usage table
```

Events (`ChatCompleted`, `EmbeddingsCompleted`) are always dispatched — listen to them for custom billing or analytics even without DB persistence.

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
          +-- IngestionService (file, text, URL -> chunk -> embed)
          +-- RetrievalService (semantic search)
          +-- ChatService (RAG chat, streaming, conversations)
          +-- ConversationManager (persistent threads)
          +-- HealthCheck (system diagnostics)
          +-- UrlFetcher (SSRF-protected HTTP fetch)
```

---

## Ingestion Pipeline

When you ingest a document (file, text, or URL), this happens automatically:

```
File/Text/URL --> Validate --> Store/Fetch --> Parse to text --> Chunk (with overlap)
    --> Batch embed --> Batch upsert to vector store --> Done
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
| HTML | .html (or via URL) | Built-in (DOMDocument) |
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
| [Agents & Tools](docs/agents-and-tools.md) | Creating custom agents and tools, ChatService parameters |
| [Ingestion Pipeline](docs/ingestion-pipeline.md) | File, text, and URL ingestion with pipeline events |
| [Vector Stores](docs/vector-stores.md) | Pinecone vs pgvector, multi-tenant scoping, batch ops |
| [Artisan Commands](docs/artisan-commands.md) | All CLI commands including `--deep` mode |
| [Testing](docs/testing.md) | How to test with fakes |

---

## Community Examples

Real-world starter prompts built and tested by the community.
Browse the [`examples/`](examples/) folder or jump straight to:

- [SaaS Chatbot Platform](examples/prompts/01-saas-chatbot-platform/) —
  Build a full multi-tenant chatbot SaaS (like Chatbase) with Laravel 13

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
