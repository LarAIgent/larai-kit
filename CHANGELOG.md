# Changelog

All notable changes to this project will be documented in this file.

## v0.1.1 - 2026-04-14

### Fixed
- **OpenAiEmbedding::embed() crash** — `toArray()` called on already-plain array; now handles both return types defensively
- **30-second PHP timeout kills ingestion** — jobs now set `set_time_limit(0)` and `$timeout = 300`; batch embedding via `embedMany()` reduces API calls 5x
- **Rate-limit and 5xx errors fail hard** — added exponential backoff with jitter to Pinecone HTTP client; configurable via `LARAI_RETRY_MAX` and `LARAI_RETRY_DELAY_MS`
- **Ingestion reports success with zero chunks** — `markState('indexed')` now fails if `chunk_count === 0`
- **SupportAgent default instructions reference "LarAIgent"** — changed to generic assistant prompt
- **Actionable error messages** — validation errors now list supported types and suggest package installs

### Added
- **Multi-tenant scoping** — `scope` parameter on `ingestText()`, `ingestFile()`, and `retrieve()`. Pinecone uses metadata `$eq` filters; pgvector uses JSON `WHERE` clauses. Prevents data leaks across customers.
- **Batch embedding** — `EmbeddingProvider::embedMany(array $texts): array` contract method. `EmbedChunksJob` now embeds all chunks in one pass instead of per-chunk loops.
- **Batch vector upsert** — `VectorStore::upsertMany(array $items): void` contract method. Pinecone batches at 100 vectors/request.
- **Scoped vector search** — `VectorStore::search()` accepts `array $scope` parameter for tenant-isolated queries.
- **ChatService expanded** — `sendMessage()` now accepts custom `Agent`, `history`, `scope`, `topK`, and `threshold` parameters. Defaults to `SupportAgent` for backward compatibility.
- **Pipeline events** — `IngestionStateChanged` event fired on every state transition for observability.
- **`larai:doctor --deep`** — live embedding probe that tests the actual API call and verifies vector dimensions match config.
- **Retry config** — `larai-kit.retry.max_attempts`, `retry.base_delay_ms`, `retry.on_status` in config.
- **`scope` column** — added to `ai_assets` migration for multi-tenant filtering.

## v0.1.0 - 2026-04-12

### Added

**Core Architecture**
- `AiKitServiceProvider` with auto-discovery, config merging, and smart contract bindings
- `FeatureDetector` with tier-based graceful degradation (Tier 0-3)
- `config/larai-kit.php` with all tunables via `.env`

**Multi-Provider Support**
- AI Providers: OpenAI, Anthropic, Gemini (switch via `LARAI_AI_PROVIDER`)
- Vector Stores: Pinecone, pgvector, none (switch via `LARAI_VECTOR_STORE`)
- Databases: MySQL and PostgreSQL (migrations adapt automatically)
- Storage: Local disk and S3

**Contracts**
- `EmbeddingProvider` — `embed()`, `embedMany()`, `dimensions()`
- `VectorStore` — `upsert()`, `upsertMany()`, `search()`, `delete()`
- `FileStorage`, `DocumentParser`, `ChatProvider`

**Vector Store Implementations**
- `PineconeVectorStore` — HTTP-based with retry/backoff, zero extra packages
- `PgVectorStore` — Eloquent-based with `whereVectorSimilarTo()`
- `NullVectorStore` — no-op fallback for graceful degradation

**Ingestion Pipeline**
- `IngestionService` orchestrator with multi-tenant scope support
- `ParseAssetJob`, `ChunkAssetJob`, `EmbedChunksJob`, `DeleteAssetVectorsJob`
- `Chunker` with configurable size and overlap
- Document parsers: `TextParser`, `PdfParser`, `DocxParser`

**RAG & Chat**
- `RetrievalService` for scoped semantic search via the VectorStore contract
- `ChatService` with RAG context injection, source citations, and custom agent support

**Models**
- `Document`, `Asset`, `Chunk`, `Ingestion` with relationships, casts, and events

**Artisan Commands**
- `larai:install`, `larai:doctor` (with `--deep`), `larai:chat`, `make:larai-agent`, `make:larai-tool`
