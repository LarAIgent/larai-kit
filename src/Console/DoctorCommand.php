<?php

namespace LarAIgent\AiKit\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use LarAIgent\AiKit\Contracts\EmbeddingProvider;
use LarAIgent\AiKit\Services\FeatureDetector;
use Throwable;

class DoctorCommand extends Command
{
    protected $signature = 'larai:doctor {--deep : Run live API tests (embedding + vector store)}';
    protected $description = 'Check the health of all LarAI Kit services';

    public function handle(FeatureDetector $features): int
    {
        $this->info('LarAI Kit Health Check');
        $this->newLine();

        $hasError = false;
        $deep = $this->option('deep');

        // Database
        $db = config('database.default');
        try {
            $start = microtime(true);
            DB::select('select 1');
            $ms = round((microtime(true) - $start) * 1000, 1);
            $this->ok("Database ({$db})", "{$ms}ms");
        } catch (Throwable $e) {
            $this->printFail("Database ({$db})", $e->getMessage());
            $hasError = true;
        }

        // AI Provider
        $provider = $features->aiProvider();
        if ($features->aiProviderReady()) {
            $this->ok("AI Provider ({$provider})");
        } else {
            $this->printFail("AI Provider ({$provider})", "missing API key — set " . strtoupper($provider) . '_API_KEY in .env');
            $hasError = true;
        }

        // Deep: live embedding test
        if ($deep && $features->aiProviderReady()) {
            try {
                $embedder = app(EmbeddingProvider::class);
                $start = microtime(true);
                $vector = $embedder->embed('hello world');
                $ms = round((microtime(true) - $start) * 1000);
                $dims = count($vector);
                $expected = $embedder->dimensions();

                if ($dims === $expected) {
                    $this->ok("Embedding probe", "{$dims} dims, {$ms}ms");
                } else {
                    $this->printFail("Embedding probe", "Expected {$expected} dims, got {$dims}");
                    $hasError = true;
                }
            } catch (Throwable $e) {
                $this->printFail("Embedding probe", $e->getMessage());
                $hasError = true;
            }
        }

        // Vector Store
        $driver = $features->vectorStoreDriver();
        if ($driver === 'none') {
            $this->skip('Vector Store', 'disabled (LARAI_VECTOR_STORE=none)');
        } elseif ($driver === 'pinecone') {
            if ($features->pineconeReady()) {
                $this->ok('Vector Store (Pinecone)');
            } else {
                $this->printFail('Vector Store (Pinecone)', 'set PINECONE_API_KEY and PINECONE_INDEX_HOST in .env');
                $hasError = true;
            }
        } elseif ($driver === 'pgvector') {
            if ($features->pgvectorReady()) {
                $this->ok('Vector Store (pgvector)');
            } else {
                $this->printFail('Vector Store (pgvector)', 'requires DB_CONNECTION=pgsql + pgvector extension');
                $hasError = true;
            }
        }

        // Storage
        $disk = $features->storageDisk();
        try {
            Storage::disk($disk)->exists('larai');
            $this->ok("Storage ({$disk})");
        } catch (Throwable $e) {
            $this->printFail("Storage ({$disk})", $e->getMessage());
            $hasError = true;
        }

        // Cache
        try {
            Cache::put('larai_doctor', 'ok', 10);
            $this->ok('Cache (' . config('cache.default') . ')');
        } catch (Throwable $e) {
            $this->printFail('Cache', $e->getMessage());
            $hasError = true;
        }

        // Redis
        if ($features->redisEnabled()) {
            try {
                Redis::connection()->ping();
                $this->ok('Redis');
            } catch (Throwable $e) {
                $this->printFail('Redis', $e->getMessage());
            }
        } else {
            $this->skip('Redis', 'not configured');
        }

        // Queue
        $queueDriver = config('queue.default');
        if ($features->queueEnabled()) {
            $this->ok("Queue ({$queueDriver})");
        } else {
            $this->skip("Queue ({$queueDriver})", 'sync mode — run php artisan queue:work for background processing');
        }

        // Summary
        $this->newLine();
        $this->line("<fg=white;options=bold>Configuration:</>");
        $this->line("  AI Provider:   {$features->aiProvider()}");
        $this->line("  Vector Store:  {$features->vectorStoreDriver()}");
        $this->line("  Database:      " . config('database.default'));
        $this->line("  Feature Tier:  {$features->tier()}");
        $this->line("  RAG:           " . ($features->ragEnabled() ? 'enabled' : 'disabled'));
        $this->line("  S3:            " . ($features->s3Enabled() ? 'enabled' : 'disabled'));

        if (! $deep) {
            $this->newLine();
            $this->line('<fg=gray>  Tip: run with --deep to test live API calls (embedding + vector store)</>' );
        }

        return $hasError ? self::FAILURE : self::SUCCESS;
    }

    private function ok(string $name, ?string $detail = null): void
    {
        $suffix = $detail ? " <fg=gray>({$detail})</>" : '';
        $this->line("  <fg=green>[OK]</>      {$name}{$suffix}");
    }

    private function printFail(string $name, ?string $note = null): void
    {
        $msg = $note ? " — {$note}" : '';
        $this->line("  <fg=red>[FAIL]</>    {$name}{$msg}");
    }

    private function skip(string $name, ?string $note = null): void
    {
        $msg = $note ? " — {$note}" : '';
        $this->line("  <fg=yellow>[SKIP]</>    {$name}{$msg}");
    }
}
