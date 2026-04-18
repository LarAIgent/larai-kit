# Changelog

All notable changes to this project will be documented in this file.

## v0.2.2 - 2026-04-18

### Fixed
- **Fatal boot error: `DoctorCommand::warn()` visibility conflict** — the `warn()` method introduced in v0.2.1 was declared `private`, but `Illuminate\Console\Command` defines a `public warn()`. PHP refuses to load the class, crashing the app on boot. Renamed to `printWarn()` (same pattern as the existing `printFail()`). Apps on v0.2.1 were unable to boot — upgrade to v0.2.2 immediately. (#4)
- **Streaming emitted raw JSON instead of text** — `ChatStreamResponse::getIterator()` fell back to `(string) $event` for non-text stream events, which serialized `StreamEnd`, tool events, etc. as their JSON representation, breaking SSE consumers. Replaced with an `extractDelta()` helper that uses duck typing on a `delta` property — only TextDelta events emit text; metadata events are silently skipped. (#4)

## v0.2.1 - 2026-04-15

### Fixed
- **Stale ingestion state on returned Asset** — `ingestFile()`, `ingestText()`, and `ingestUrl()` now call `$asset->load('ingestion')` before returning, so callers always get the final pipeline state without needing `$asset->fresh()` (#2)
- **Missing upgrade migration for `scope` column** — v0.2.0 modified the original assets migration in-place, breaking users upgrading from v0.1.x. Added `2026_04_14_000008_add_scope_to_ai_assets_table.php` with `hasColumn` guard (#2)
- **No warning when optional parsers are missing** — `larai:doctor` and the health endpoint now show `[WARN] Pdf parser — install smalot/pdfparser to enable` and `[WARN] Docx parser — install phpoffice/phpword to enable` instead of letting users discover this via runtime crash (#2)

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
