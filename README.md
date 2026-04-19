[![Latest Version on Packagist](https://img.shields.io/packagist/v/laraigent/larai-kit.svg?style=flat-square)](https://packagist.org/packages/laraigent/larai-kit)
[![Total Downloads](https://img.shields.io/packagist/dt/laraigent/larai-kit.svg?style=flat-square)](https://packagist.org/packages/laraigent/larai-kit)
[![PHP Version](https://img.shields.io/packagist/php-v/laraigent/larai-kit.svg?style=flat-square)](https://packagist.org/packages/laraigent/larai-kit)
[![Laravel 12 / 13](https://img.shields.io/badge/Laravel-12%20%7C%2013-ff2d20?style=flat-square&logo=laravel)](https://laravel.com)
[![License](https://img.shields.io/packagist/l/laraigent/larai-kit.svg?style=flat-square)](LICENSE)

# LarAI Kit — Laravel RAG & AI Agent Toolkit

**Production-ready RAG, AI agents, document ingestion, vector search, and streaming chat for Laravel 12 and 13.** Drop-in package built on top of [Laravel's first-party AI SDK](https://laravel.com/blog/introducing-the-laravel-ai-sdk) — works with **OpenAI**, **Anthropic**, and **Gemini**; supports **Pinecone** and **pgvector** for vector search; parses **PDF**, **DOCX**, plain text, and URLs out of the box.

> Install in 30 seconds. No lock-in. Fully backward compatible. Multi-tenant ready.

```bash
composer require laraigent/larai-kit
```

---

## Table of Contents

- [Why LarAI Kit?](#why-larai-kit)
- [Use Cases](#use-cases)
- [Features](#features)
- [Quickstart](#quickstart)
- [Feature Tiers](#feature-tiers)
- [Configuration](#configuration)
- [Usage](#usage)
  - [Chat with RAG context](#chat-with-rag-context)
  - [Conversations (persistent chat threads)](#conversations-persistent-chat-threads)
  - [Streaming chat (SSE)](#streaming-chat-sse)
  - [Ingest documents](#ingest-documents)
  - [Create a custom agent](#create-a-custom-agent)
  - [Web health endpoint (JSON)](#web-health-endpoint-json)
- [Artisan Commands](#artisan-commands)
- [Architecture](#architecture)
- [Ingestion Pipeline](#ingestion-pipeline)
- [Supported File Types](#supported-file-types)
- [Requirements](#requirements)
- [Documentation](#documentation)
- [FAQ](#faq)
- [Contributing](#contributing)
- [License](#license)

---

## Why LarAI Kit?

Laravel's AI SDK gives you the low-level building blocks (`Agent`, `Tool`, `prompt()`). But building a production **RAG (Retrieval-Augmented Generation) pipeline** for a Laravel app still requires a lot of plumbing:

- How do I parse PDFs and DOCX files into clean text?
- How do I chunk documents and generate OpenAI / Anthropic / Gemini embeddings?
- How do I store and search vectors in **Pinecone** or **pgvector**?
- How do I inject relevant context into my agent's prompt without hand-crafting it?
- How do I scope retrieval per tenant so customers never see each other's data?
- How do I make all this **not break** when one service is unavailable?

**LarAI Kit solves all of this.** One `composer require`, a few `.env` keys, and you have a working Laravel RAG pipeline with source-cited answers and streaming SSE chat.

---

## Use Cases

LarAI Kit is the fastest path to building:

- **Customer support chatbots** — RAG over your FAQ, help-center docs, product manuals, and policies with automatic source citations
- **Internal knowledge search** — semantic search over Confluence exports, Notion dumps, wikis, and internal PDFs for your team
- **Multi-tenant SaaS chatbots** — scope every ingest, retrieval, and conversation per customer (Chatbase-style platforms, but self-hosted)
- **Document Q&A** — upload PDFs, Word docs, or URLs; ask natural-language questions over them
- **AI agents with tools** — booking agents, product-lookup agents, order-status bots with custom tool calling
- **Streaming AI responses** — Server-Sent Events (SSE) streaming with token-by-token deltas and final source attribution
- **Compliance-aware AI features** — Laravel-native scoping and per-tenant isolation that plays nicely with your existing auth/authorization

---

## Features

| Feature | Description |
|---|---|
| **AI Agents + Tools** | Pre-built `SupportAgent` (RAG) and `BookingAgent` (tools) + `make:larai-agent` and `make:larai-tool` scaffolding |
| **Document Ingestion** | Upload text, PDF, DOCX, or URL → auto parse → chunk with overlap → batch embed → batch upsert |
| **URL Ingestion** | `ingestUrl()` with SSRF protection, HTML/text parsing, chunking, and embedding |
| **Vector Search** | Pinecone (default) or pgvector — swappable via `.env`, with retry/backoff and scoped search |
| **RAG Chat** | Retrieves top-k relevant context, injects into prompt, returns reply + source citations |
| **Streaming Chat (SSE)** | `streamMessage()` returns an SSE-ready response — token-by-token deltas with sources at the end |
| **Persistent Conversations** | Chat threads via `Conversation` + `Message` models (UUID PKs, scope-aware, history-limited) |
| **Multi-Tenant Scoping** | Scope ingestion, retrieval, and conversations per tenant — prevents data leaks between customers |
| **Usage & Cost Tracking** | `ChatCompleted` + `EmbeddingsCompleted` events with real token counts; opt-in DB persistence via `ai_usage` |
| **Graceful Degradation** | App works at every tier — missing services disable features, never crash your app |
| **Multi-Provider LLMs** | OpenAI, Anthropic (Claude), Gemini — switch via `LARAI_AI_PROVIDER` |
| **Multi-Database** | MySQL 8+ (default) or PostgreSQL 14+ — migrations adapt automatically |
| **Batch Operations** | `embedMany()` and `upsertMany()` — ~100x fewer API calls on large documents |
| **Health Endpoint** | JSON health check at `/_larai/health` (env-configurable middleware) + `larai:doctor --deep` CLI |
| **Artisan Commands** | `larai:install`, `larai:doctor`, `larai:chat`, `make:larai-agent`, `make:larai-tool` |

---

## Quickstart

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

You now have a RAG-enabled AI agent wired into your Laravel app — ingest your first document and chat with it:

```php
use LarAIgent\AiKit\Services\Ingestion\IngestionService;
use LarAIgent\AiKit\Services\Chat\ChatService;

app(IngestionService::class)->ingestText('Our return policy allows returns within 30 days of purchase.');

$reply = app(ChatService::class)->sendMessage('How long do I have to return a product?');
// $reply['reply']   => "You have 30 days from the date of purchase..."
// $reply['sources'] => [{ name, url, type }, ...]
```

### Before you go to production — three things to know

1. The namespace is `LarAIgent\AiKit\...` (case-sensitive — matters on Linux deployments)
2. Run `php artisan queue:work` in a separate process, or set `QUEUE_CONNECTION=sync` in `.env` — otherwise ingestion jobs queue silently
3. OpenAI tier-1 keys allow ~3 RPM for embeddings — a 50KB document may rate-limit you. Set `LARAI_RETRY_MAX=5` or upgrade your OpenAI tier.

---

## Feature Tiers

LarAI Kit detects which services are available and enables features accordingly — never crashes on missing config:

| Tier | What you configure | What works |
|---|---|---|
| **0** | Nothing | App boots; AI features disabled gracefully |
| **1** | `OPENAI_API_KEY` (or Anthropic / Gemini) | Chat works (no RAG) |
| **2** | + `PINECONE_API_KEY` (or pgvector) | Chat + RAG (semantic search over your documents) |
| **3** | + AWS S3 credentials | Chat + RAG + cloud file storage |

---

## Configuration

All settings are managed via `.env`. No code changes to switch providers.

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
# Just set LARAI_VECTOR_STORE=pgvector — no extra keys needed
```

### Database

```env
DB_CONNECTION=mysql          # mysql or pgsql — both fully supported
```

### Full Config Reference

Run `php artisan vendor:publish --tag=larai-kit-config` to publish `config/larai-kit.php`, then see [Configuration Docs](docs/configuration.md) for all tunables.

---

## Usage

### Chat with RAG context

```php
use LarAIgent\AiKit\Services\Chat\ChatService;

$chat = app(ChatService::class);
$result = $chat->sendMessage('What is your return policy?');

$result['reply'];   // AI response grounded in your ingested documents
$result['sources']; // [{ name, url, type }, ...] for citation UI
```

### Conversations (persistent chat threads)

```php
use LarAIgent\AiKit\Services\Chat\ConversationManager;

$conversations = app(ConversationManager::class);
$chat = app(ChatService::class);

// Create a scoped conversation
$convo = $conversations->create(scope: ['chatbot_id' => 42], title: 'Support thread');

// Send messages — history auto-loaded and persisted
$result = $chat->sendMessage('What is the return policy?', conversationId: $convo->id);
$result = $chat->sendMessage('How long do I have?', conversationId: $convo->id);
// Both messages + replies are stored in ai_messages
```

### Streaming chat (SSE)

```php
// In a controller — return directly for automatic SSE response
public function stream(Request $request)
{
    $chat = app(ChatService::class);
    return $chat->streamMessage($request->input('message'));
    // Emits:  data: {"type":"text_delta","delta":"..."}  (per chunk)
    //         data: {"type":"sources","sources":[...]}    (at the end)
    //         data: {"type":"done"}
}
```

### Ingest documents

```php
use LarAIgent\AiKit\Services\Ingestion\IngestionService;

$ingestion = app(IngestionService::class);

// Ingest plain text
$asset = $ingestion->ingestText('Our return policy allows returns within 30 days...');

// Ingest a file (PDF, DOCX, TXT)
$asset = $ingestion->ingestFile($request->file('document'));

// Ingest a URL (with SSRF protection for private networks)
$asset = $ingestion->ingestUrl('https://example.com/docs/faq');

// With multi-tenant scope — retrieval will only see this tenant's data
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
LARAI_HEALTH_MIDDLEWARE=auth       # pipe-separated; leave empty for public
```

```bash
curl http://localhost/_larai/health?deep=true
```

Returns structured JSON with all check results — wire into monitoring dashboards (Datadog, New Relic, Grafana, UptimeRobot, etc.). Set `LARAI_HEALTH_MIDDLEWARE=` (empty) to expose publicly behind an ingress allowlist, or `LARAI_HEALTH_MIDDLEWARE=throttle:10,1` to rate-limit without auth. Use `|` to stack (e.g. `auth|throttle:60,1`) — commas inside a single middleware parameter (Laravel's rate-limit syntax) are preserved.

### Usage tracking

```env
LARAI_TRACK_USAGE=true   # Records to ai_usage table
```

Events (`ChatCompleted`, `EmbeddingsCompleted`) are always dispatched with real provider-reported token counts — listen for custom billing, analytics, or cost dashboards even without DB persistence.

### Interactive CLI chat

```bash
php artisan larai:chat
```

---

## Artisan Commands

| Command | Description |
|---|---|
| `php artisan larai:install` | Publish config, run migrations, create storage link |
| `php artisan larai:doctor` | Check health of all services (add `--deep` for live API probes) |
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
          +-- FeatureDetector    (detects what's available)
          +-- EmbeddingProvider  (generates vectors)
          +-- VectorStore        (stores/searches vectors)
          +-- FileStorage        (stores uploaded files)
          +-- IngestionService   (file, text, URL -> chunk -> embed)
          +-- RetrievalService   (semantic search)
          +-- ChatService        (RAG chat, streaming, conversations)
          +-- ConversationManager (persistent threads)
          +-- HealthCheck        (system diagnostics)
          +-- UrlFetcher         (SSRF-protected HTTP fetch)
```

---

## Ingestion Pipeline

When you ingest a document (file, text, or URL), this happens automatically:

```
File/Text/URL --> Validate --> Store/Fetch --> Parse to text --> Chunk (with overlap)
    --> Batch embed --> Batch upsert to vector store --> Done
```

Each step is a queued job (or runs synchronously when `QUEUE_CONNECTION=sync`):
- `ParseAssetJob` — extracts text from PDF, DOCX, HTML, or plain text
- `ChunkAssetJob` — splits into overlapping chunks (configurable size)
- `EmbedChunksJob` — generates embeddings and upserts to Pinecone / pgvector

State is tracked in the `ai_ingestions` table: `queued → parsing → chunking → embedding → indexed`. Subscribe to `AssetIndexed` / `AssetFailed` events for post-ingest workflows (see [Ingestion Pipeline Docs](docs/ingestion-pipeline.md)).

---

## Supported File Types

| Type | Extension | Parser |
|---|---|---|
| Plain text | `.txt`, `.md`, `.csv` | Built-in |
| HTML | `.html` (or via URL) | Built-in (`DOMDocument`) |
| PDF | `.pdf` | Requires `smalot/pdfparser` |
| Word | `.docx` | Requires `phpoffice/phpword` |

Install optional parsers:
```bash
composer require smalot/pdfparser phpoffice/phpword
```

---

## Requirements

- **PHP** 8.3+
- **Laravel** 12 or 13
- **`laravel/ai`** ^0.4
- **Database**: MySQL 8+ or PostgreSQL 14+
- **AI provider API key**: OpenAI, Anthropic, or Gemini
- **Vector store (optional)**: Pinecone account or PostgreSQL with pgvector extension

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

Real-world starter prompts built and tested by the community. Browse [`examples/`](examples/) or jump straight to:

- [SaaS Chatbot Platform](examples/prompts/01-saas-chatbot-platform/) — Build a full multi-tenant chatbot SaaS (like Chatbase) with Laravel 13

---

## FAQ

### How is LarAI Kit different from the Laravel AI SDK?

The [Laravel AI SDK](https://laravel.com/blog/introducing-the-laravel-ai-sdk) provides low-level primitives: `Agent`, `Tool`, `prompt()`, embeddings, streaming. **LarAI Kit is additive** — it sits on top and provides the RAG pipeline (document parsing, chunking, embedding, vector storage, retrieval, prompt injection, source citations) that the SDK intentionally leaves out. You still use the SDK directly for anything custom; LarAI Kit just saves you from rebuilding the common pieces.

### Can I use LarAI Kit without Pinecone?

Yes. Set `LARAI_VECTOR_STORE=pgvector` to use PostgreSQL's pgvector extension, or `LARAI_VECTOR_STORE=none` to disable RAG entirely (chat still works). No code changes.

### Does it work with Laravel 11?

No — LarAI Kit requires Laravel 12 or 13 because it depends on `laravel/ai ^0.4`, which itself requires the Laravel 12+ container. If you're on Laravel 11, upgrade first (the upgrade from 11 → 12 is typically small).

### Which LLM providers are supported?

OpenAI (default), Anthropic (Claude 3.5 Sonnet / 4), and Google Gemini. Switch via `LARAI_AI_PROVIDER=openai|anthropic|gemini`. Chat, streaming, and tool calling all route through the same `Agent` contract — your code doesn't change when you switch providers.

### How do I ingest a PDF?

```bash
composer require smalot/pdfparser
```
```php
app(IngestionService::class)->ingestFile($request->file('document'));
```
The pipeline detects the MIME type, routes through `PdfParser`, chunks with overlap, embeds in batches, and upserts to your vector store. Check `$asset->ingestion->state` for the final status.

### How does multi-tenant scoping work?

Pass a `scope` array on every ingest / retrieve / conversation call:
```php
$ingestion->ingestText($text, scope: ['chatbot_id' => 42]);
$chat->sendMessage($message, scope: ['chatbot_id' => 42]);
```
Pinecone uses metadata `$eq` filters under the hood; pgvector uses JSON `WHERE` clauses. Tenants never see each other's vectors — crucial for SaaS chatbot platforms where each customer uploads their own knowledge base.

### Is streaming SSE supported out of the box?

Yes. `ChatService::streamMessage()` returns a `ChatStreamResponse` that implements Laravel's `Responsable` interface — return it directly from a controller and you get a fully-formed SSE response (token-by-token deltas, sources at the end, `[done]` event). No manual `StreamedResponse` wiring needed.

### How do I track AI usage and costs?

Listen for the `ChatCompleted` and `EmbeddingsCompleted` events — they carry real provider-reported token counts:
```php
Event::listen(ChatCompleted::class, function ($event) {
    Log::info('AI usage', [
        'provider' => $event->provider,
        'model' => $event->model,
        'input' => $event->inputTokens,
        'output' => $event->outputTokens,
        'scope' => $event->scope,
    ]);
});
```
Or set `LARAI_TRACK_USAGE=true` to persist automatically to the `ai_usage` table.

### Can I use LarAI Kit with an existing Laravel AI SDK app?

Yes — LarAI Kit adds services without touching anything the SDK provides. Your existing `Agent` classes, tools, and `prompt()` calls keep working unchanged. LarAI Kit just gives you the RAG layer on top.

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
- Inspired by the gap between Python RAG frameworks (LangChain, LlamaIndex) and the Laravel ecosystem — Laravel developers deserve first-class RAG tooling too
