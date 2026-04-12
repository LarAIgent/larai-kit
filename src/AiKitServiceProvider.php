<?php

namespace LarAIgent\AiKit;

use Illuminate\Support\ServiceProvider;
use LarAIgent\AiKit\Console\ChatCommand;
use LarAIgent\AiKit\Console\DoctorCommand;
use LarAIgent\AiKit\Console\InstallCommand;
use LarAIgent\AiKit\Console\MakeAgentCommand;
use LarAIgent\AiKit\Console\MakeToolCommand;
use LarAIgent\AiKit\Contracts\EmbeddingProvider;
use LarAIgent\AiKit\Contracts\FileStorage;
use LarAIgent\AiKit\Contracts\VectorStore;
use LarAIgent\AiKit\Services\Chat\ChatService;
use LarAIgent\AiKit\Services\Embedding\NullEmbedding;
use LarAIgent\AiKit\Services\Embedding\OpenAiEmbedding;
use LarAIgent\AiKit\Services\FeatureDetector;
use LarAIgent\AiKit\Services\Ingestion\Chunker;
use LarAIgent\AiKit\Services\Ingestion\IngestionService;
use LarAIgent\AiKit\Services\Ingestion\Parsers\ParserRegistry;
use LarAIgent\AiKit\Services\Retrieval\RetrievalService;
use LarAIgent\AiKit\Services\Storage\LocalStorage;
use LarAIgent\AiKit\Services\Storage\NullStorage;
use LarAIgent\AiKit\Services\Storage\S3Storage;
use LarAIgent\AiKit\Services\VectorStore\NullVectorStore;
use LarAIgent\AiKit\Services\VectorStore\PgVectorStore;
use LarAIgent\AiKit\Services\VectorStore\PineconeVectorStore;

class AiKitServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/larai-kit.php', 'larai-kit');

        $this->app->singleton(FeatureDetector::class);

        // Vector store — resolved by config
        $this->app->singleton(VectorStore::class, function ($app) {
            $driver = config('larai-kit.vector_store', 'pinecone');

            return match ($driver) {
                'pinecone' => tap(new PineconeVectorStore(), function ($store) {
                    // Fall back to null if not configured
                    if (! $store->isConfigured()) {
                        $this->app->instance(VectorStore::class, new NullVectorStore());
                    }
                }),
                'pgvector' => tap(new PgVectorStore(), function ($store) {
                    if (! $store->isConfigured()) {
                        $this->app->instance(VectorStore::class, new NullVectorStore());
                    }
                }),
                default => new NullVectorStore(),
            };
        });

        // Storage adapter
        $this->app->singleton(FileStorage::class, function ($app) {
            $features = $app->make(FeatureDetector::class);

            if ($features->s3Enabled()) {
                return new S3Storage();
            }

            return new LocalStorage();
        });

        // Embedding provider
        $this->app->singleton(EmbeddingProvider::class, function ($app) {
            $features = $app->make(FeatureDetector::class);

            if ($features->aiProviderReady()) {
                return new OpenAiEmbedding();
            }

            return new NullEmbedding();
        });

        $this->app->singleton(ParserRegistry::class);
        $this->app->singleton(Chunker::class);

        $this->app->singleton(IngestionService::class, function ($app) {
            return new IngestionService(
                $app->make(FileStorage::class),
                $app->make(ParserRegistry::class),
            );
        });

        $this->app->singleton(RetrievalService::class, function ($app) {
            return new RetrievalService(
                $app->make(EmbeddingProvider::class),
                $app->make(VectorStore::class),
                $app->make(FeatureDetector::class),
            );
        });

        $this->app->singleton(ChatService::class, function ($app) {
            return new ChatService(
                $app->make(RetrievalService::class),
                $app->make(FeatureDetector::class),
            );
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/larai-kit.php' => config_path('larai-kit.php'),
        ], 'larai-kit-config');

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
                DoctorCommand::class,
                MakeAgentCommand::class,
                MakeToolCommand::class,
                ChatCommand::class,
            ]);
        }
    }
}
