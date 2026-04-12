# Changelog

All notable changes to this project will be documented in this file.

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
- `EmbeddingProvider` - generate vector embeddings
- `VectorStore` - upsert, search, delete vectors
- `FileStorage` - store, retrieve, delete files
- `DocumentParser` - parse files to plain text
- `ChatProvider` - send messages with context

**Vector Store Implementations**
- `PineconeVectorStore` - HTTP-based, zero extra packages, uses Laravel Http facade
- `PgVectorStore` - Eloquent-based with `whereVectorSimilarTo()`
- `NullVectorStore` - no-op fallback for graceful degradation

**Ingestion Pipeline**
- `IngestionService` orchestrator (validate -> store -> parse -> chunk -> embed)
- `ParseAssetJob`, `ChunkAssetJob`, `EmbedChunksJob`, `DeleteAssetVectorsJob`
- `Chunker` with configurable size and overlap
- Document parsers: `TextParser`, `PdfParser`, `DocxParser`
- `ParserRegistry` for mime-type-based parser resolution

**RAG & Chat**
- `RetrievalService` for semantic search via the VectorStore contract
- `ChatService` with automatic RAG context injection and source citations

**Models**
- `Document`, `Asset`, `Chunk`, `Ingestion` with relationships and casts

**Agents & Tools**
- `SupportAgent` (RAG-enabled example)
- `BookingAgent` (tool-using example)
- `BookAppointmentTool` (example tool)

**Artisan Commands**
- `larai:install` - publish config, migrate, storage link
- `larai:doctor` - health check for all services
- `larai:chat` - interactive CLI chat
- `make:larai-agent` - scaffold agent class
- `make:larai-tool` - scaffold tool class

**Embedding**
- `OpenAiEmbedding` using Laravel AI SDK's `Str::of()->toEmbeddings()`
- `NullEmbedding` fallback (returns zero vectors)

**Storage**
- `LocalStorage`, `S3Storage`, `NullStorage` adapters
