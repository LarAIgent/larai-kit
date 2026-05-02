<?php

namespace LarAIgent\AiKit\Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use LarAIgent\AiKit\Contracts\EmbeddingProvider;
use LarAIgent\AiKit\Contracts\FileStorage;
use LarAIgent\AiKit\Contracts\VectorStore;
use LarAIgent\AiKit\Models\Chunk;
use LarAIgent\AiKit\Services\Ingestion\IngestionService;
use LarAIgent\AiKit\Services\Ingestion\Parsers\ParserRegistry;
use LarAIgent\AiKit\Services\Ingestion\UrlFetcher;
use LarAIgent\AiKit\Tests\TestCase;

class ReleaseSmokeTest extends TestCase
{
    private string $sqlitePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configureFileBackedSqlite();
        $this->artisan('migrate:fresh')->run();
        $this->ensureDatabaseQueueTables();
    }

    protected function tearDown(): void
    {
        if (isset($this->sqlitePath) && is_file($this->sqlitePath)) {
            @unlink($this->sqlitePath);
        }

        parent::tearDown();
    }

    public function test_sync_queue_end_to_end_ingest_url_and_delete(): void
    {
        config(['queue.default' => 'sync']);

        $vectorStore = new SmokeVectorStore();
        $this->app->instance(VectorStore::class, $vectorStore);
        $this->app->instance(EmbeddingProvider::class, new SmokeEmbeddingProvider());

        $stubFetcher = new class extends UrlFetcher {
            public function assertSafe(string $url): void {}

            public function fetch(string $url): array
            {
                return [
                    'body' => '<html><body><article>Smoke URL ingestion content</article></body></html>',
                    'content_type' => 'text/html',
                    'url' => $url,
                ];
            }
        };
        $this->app->instance(UrlFetcher::class, $stubFetcher);

        $service = new IngestionService(
            app(FileStorage::class),
            app(ParserRegistry::class),
            $stubFetcher,
        );

        $textAsset = $service->ingestText('Smoke text ingestion content for release checks.');
        $textAsset->refresh()->load('ingestion');
        $this->assertSame('indexed', $textAsset->ingestion->state);

        $urlAsset = $service->ingestUrl('https://example.com/smoke');
        $urlAsset->refresh()->load('ingestion');
        $this->assertSame('indexed', $urlAsset->ingestion->state);

        $this->assertNotEmpty($vectorStore->upsertedChunkIds);

        $deletedCandidateIds = Chunk::where('asset_id', $textAsset->id)->pluck('id')->all();
        $textAsset->delete();

        $deleted = array_merge(...$vectorStore->deletedBatches ?: [[]]);
        $this->assertNotEmpty(array_intersect($deletedCandidateIds, $deleted));
    }

    public function test_database_queue_worker_smoke_for_ingest_and_delete(): void
    {
        config([
            'queue.default' => 'database',
            'queue.connections.database.driver' => 'database',
            'queue.connections.database.table' => 'jobs',
            'queue.connections.database.queue' => 'default',
            'queue.connections.database.retry_after' => 90,
            'queue.connections.database.after_commit' => false,
        ]);

        $vectorStore = new SmokeVectorStore();
        $this->app->instance(VectorStore::class, $vectorStore);
        $this->app->instance(EmbeddingProvider::class, new SmokeEmbeddingProvider());

        $service = app(IngestionService::class);
        $asset = $service->ingestText('Database queue smoke flow content.');
        $asset->refresh()->load('ingestion');

        $this->assertSame('queued', $asset->ingestion->state);
        $this->assertGreaterThan(0, DB::table('jobs')->count());

        $this->drainDatabaseQueue();

        $asset->refresh()->load('ingestion');
        $this->assertSame('indexed', $asset->ingestion->state);

        $chunkIds = Chunk::where('asset_id', $asset->id)->pluck('id')->all();
        $asset->delete();
        $this->assertGreaterThan(0, DB::table('jobs')->count());

        $this->drainDatabaseQueue();

        $deleted = array_merge(...$vectorStore->deletedBatches ?: [[]]);
        $this->assertNotEmpty(array_intersect($chunkIds, $deleted));
    }

    private function configureFileBackedSqlite(): void
    {
        $this->sqlitePath = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR
            . 'larai-kit-smoke-' . bin2hex(random_bytes(8)) . '.sqlite';

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => $this->sqlitePath,
            'database.connections.sqlite.foreign_key_constraints' => true,
        ]);

        DB::purge('sqlite');
        DB::reconnect('sqlite');
    }

    private function ensureDatabaseQueueTables(): void
    {
        if (! Schema::hasTable('jobs')) {
            Schema::create('jobs', function (Blueprint $table) {
                $table->id();
                $table->string('queue')->index();
                $table->longText('payload');
                $table->unsignedTinyInteger('attempts');
                $table->unsignedInteger('reserved_at')->nullable();
                $table->unsignedInteger('available_at');
                $table->unsignedInteger('created_at');
            });
        }

        if (! Schema::hasTable('failed_jobs')) {
            Schema::create('failed_jobs', function (Blueprint $table) {
                $table->id();
                $table->string('uuid')->unique();
                $table->text('connection');
                $table->text('queue');
                $table->longText('payload');
                $table->longText('exception');
                $table->timestamp('failed_at')->useCurrent();
            });
        }
    }

    private function drainDatabaseQueue(): void
    {
        $safety = 0;
        while (DB::table('jobs')->count() > 0 && $safety < 20) {
            Artisan::call('queue:work', [
                '--once' => true,
                '--queue' => 'default',
                '--tries' => 1,
                '--timeout' => 60,
            ]);
            $safety++;
        }
    }
}

class SmokeEmbeddingProvider implements EmbeddingProvider
{
    public function embed(string $text): array
    {
        return [0.1, 0.2];
    }

    public function embedMany(array $texts): array
    {
        return array_map(fn (string $text) => [0.1, (float) max(strlen($text), 1)], $texts);
    }

    public function dimensions(): int
    {
        return 2;
    }
}

class SmokeVectorStore implements VectorStore
{
    /** @var array<int, int> */
    public array $upsertedChunkIds = [];

    /** @var array<int, array<int, int>> */
    public array $deletedBatches = [];

    public function upsert(int $chunkId, array $embedding, array $metadata = []): void
    {
        $this->upsertedChunkIds[] = $chunkId;
    }

    public function upsertMany(array $items): void
    {
        foreach ($items as $item) {
            $this->upsertedChunkIds[] = (int) ($item['chunk_id'] ?? 0);
        }
    }

    public function search(array $embedding, int $limit = 5, float $threshold = 0.4, array $scope = []): Collection
    {
        return collect();
    }

    public function delete(array $chunkIds): void
    {
        $this->deletedBatches[] = array_values(array_map('intval', $chunkIds));
    }
}
