<?php

namespace LarAIgent\AiKit\Services;

use Illuminate\Support\Facades\DB;
use LarAIgent\AiKit\Contracts\VectorStore;

class FeatureDetector
{
    /**
     * Check if any AI provider has credentials configured.
     */
    public function aiProviderReady(): bool
    {
        return match ($this->aiProvider()) {
            'openai' => ! empty(env('OPENAI_API_KEY')),
            'anthropic' => ! empty(env('ANTHROPIC_API_KEY')),
            'gemini' => ! empty(env('GEMINI_API_KEY')),
            default => false,
        };
    }

    /**
     * Which AI provider is selected.
     */
    public function aiProvider(): string
    {
        return config('larai-kit.ai_provider', 'openai');
    }

    /**
     * Which vector store is selected.
     */
    public function vectorStoreDriver(): string
    {
        return config('larai-kit.vector_store', 'pinecone');
    }

    /**
     * Check if the selected vector store has valid credentials/setup.
     */
    public function vectorStoreReady(): bool
    {
        return match ($this->vectorStoreDriver()) {
            'pinecone' => $this->pineconeReady(),
            'pgvector' => $this->pgvectorReady(),
            default => false,
        };
    }

    /**
     * RAG is enabled when we have an AI provider AND a working vector store.
     */
    public function ragEnabled(): bool
    {
        return $this->aiProviderReady() && $this->vectorStoreReady();
    }

    public function pineconeReady(): bool
    {
        $key = config('larai-kit.pinecone.api_key', '');
        $host = config('larai-kit.pinecone.index_host', '');

        return ! empty($key) && ! empty($host);
    }

    public function pgvectorReady(): bool
    {
        if (config('database.default') !== 'pgsql') {
            return false;
        }

        try {
            $result = DB::select("SELECT 1 FROM pg_extension WHERE extname = 'vector' LIMIT 1");
            return ! empty($result);
        } catch (\Throwable) {
            return false;
        }
    }

    public function s3Enabled(): bool
    {
        $key = config('filesystems.disks.s3.key');
        $secret = config('filesystems.disks.s3.secret');
        $bucket = config('filesystems.disks.s3.bucket');

        return ! empty($key) && ! empty($secret) && ! empty($bucket);
    }

    public function queueEnabled(): bool
    {
        return config('queue.default') !== 'sync';
    }

    public function redisEnabled(): bool
    {
        return in_array(config('cache.default'), ['redis'])
            || in_array(config('queue.default'), ['redis']);
    }

    public function storageDisk(): string
    {
        if ($this->s3Enabled()) {
            return 's3';
        }

        return config('larai-kit.storage_disk', 'public');
    }

    /**
     * Tier 0: No AI key
     * Tier 1: Chat only (AI key but no vector store)
     * Tier 2: Chat + RAG (AI key + vector store)
     * Tier 3: Chat + RAG + Cloud (AI key + vector store + S3)
     */
    public function tier(): int
    {
        if (! $this->aiProviderReady()) {
            return 0;
        }

        if ($this->ragEnabled() && $this->s3Enabled()) {
            return 3;
        }

        if ($this->ragEnabled()) {
            return 2;
        }

        return 1;
    }

    public function toArray(): array
    {
        return [
            'ai_provider' => $this->aiProvider(),
            'ai_provider_ready' => $this->aiProviderReady(),
            'vector_store' => $this->vectorStoreDriver(),
            'vector_store_ready' => $this->vectorStoreReady(),
            'rag_enabled' => $this->ragEnabled(),
            's3_enabled' => $this->s3Enabled(),
            'queue_async' => $this->queueEnabled(),
            'redis_enabled' => $this->redisEnabled(),
            'storage_disk' => $this->storageDisk(),
            'database' => config('database.default'),
            'tier' => $this->tier(),
        ];
    }
}
