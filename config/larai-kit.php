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

];
