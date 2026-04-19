<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AI Provider
    |--------------------------------------------------------------------------
    | Supported: "openai", "anthropic", "gemini"
    | The provider used for chat completions and embeddings.
    */

    'ai_provider' => env('LARAI_AI_PROVIDER', 'openai'),

    'models' => [
        'chat' => env('LARAI_CHAT_MODEL', 'gpt-4o-mini'),
        'embedding' => env('LARAI_EMBEDDING_MODEL', 'text-embedding-3-small'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Vector Store
    |--------------------------------------------------------------------------
    | Supported: "pinecone", "pgvector", "none"
    | Default is "pinecone" — works with any database, no compilation needed.
    | Set to "pgvector" if you have PostgreSQL + pgvector extension.
    | Set to "none" to disable RAG entirely (chat-only mode).
    */

    'vector_store' => env('LARAI_VECTOR_STORE', 'pinecone'),

    /*
    |--------------------------------------------------------------------------
    | Pinecone (when vector_store = pinecone)
    |--------------------------------------------------------------------------
    */

    'pinecone' => [
        'api_key' => env('PINECONE_API_KEY', ''),
        'index_host' => env('PINECONE_INDEX_HOST', ''),  // e.g. https://my-index-abc123.svc.aped-1234.pinecone.io
    ],

    /*
    |--------------------------------------------------------------------------
    | Embedding
    |--------------------------------------------------------------------------
    */

    'embedding_dimensions' => (int) env('LARAI_EMBEDDING_DIMENSIONS', 1536),
    'similarity_threshold' => (float) env('LARAI_SIMILARITY_THRESHOLD', 0.4),
    'rag_top_k' => (int) env('LARAI_RAG_TOP_K', 5),

    /*
    |--------------------------------------------------------------------------
    | Chunking
    |--------------------------------------------------------------------------
    */

    'chunk_size' => (int) env('LARAI_CHUNK_SIZE', 512),
    'chunk_overlap' => (int) env('LARAI_CHUNK_OVERLAP', 50),

    /*
    |--------------------------------------------------------------------------
    | Storage
    |--------------------------------------------------------------------------
    */

    'storage_disk' => env('LARAI_STORAGE_DISK', 'public'),
    'storage_path' => 'larai/documents',

    /*
    |--------------------------------------------------------------------------
    | File Upload
    |--------------------------------------------------------------------------
    */

    'max_file_size_mb' => (int) env('LARAI_MAX_FILE_MB', 20),

    'allowed_mime_types' => [
        'text/plain',
        'text/markdown',
        'text/csv',
        'application/pdf',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'image/jpeg',
        'image/png',
        'audio/mpeg',
        'audio/wav',
        'video/mp4',
    ],

    /*
    |--------------------------------------------------------------------------
    | Chat / Conversation
    |--------------------------------------------------------------------------
    */

    'conversation_history_turns' => (int) env('LARAI_HISTORY_TURNS', 10),

    /*
    |--------------------------------------------------------------------------
    | Retry / Backoff (for API rate-limits and 5xx errors)
    |--------------------------------------------------------------------------
    */

    'retry' => [
        'max_attempts' => (int) env('LARAI_RETRY_MAX', 3),
        'base_delay_ms' => (int) env('LARAI_RETRY_DELAY_MS', 1000),
        'on_status' => [429, 500, 502, 503, 504],
    ],

    /*
    |--------------------------------------------------------------------------
    | Usage Tracking
    |--------------------------------------------------------------------------
    | When enabled, ChatCompleted and EmbeddingsCompleted events are recorded
    | to the ai_usage table automatically. Events are always dispatched
    | regardless of this setting — this only controls persistence.
    */

    'track_usage' => (bool) env('LARAI_TRACK_USAGE', false),

    /*
    |--------------------------------------------------------------------------
    | URL Ingestion
    |--------------------------------------------------------------------------
    */

    'url_ingestion' => [
        'timeout' => (int) env('LARAI_URL_TIMEOUT', 30),
        'max_redirects' => (int) env('LARAI_URL_MAX_REDIRECTS', 5),
        'max_size_mb' => (int) env('LARAI_URL_MAX_SIZE_MB', 10),
        'user_agent' => env('LARAI_URL_USER_AGENT', 'LarAIgent/1.0'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Web Health Endpoint
    |--------------------------------------------------------------------------
    | Exposes a JSON health endpoint at the configured path.
    | Disabled by default — enable in production for monitoring.
    */

    'health_endpoint' => [
        'enabled' => (bool) env('LARAI_HEALTH_ENABLED', false),
        'path' => env('LARAI_HEALTH_PATH', '_larai/health'),
        'middleware' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('LARAI_HEALTH_MIDDLEWARE', 'auth'))
        ))),
    ],

];
