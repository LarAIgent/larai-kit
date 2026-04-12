<?php

namespace LarAIgent\AiKit\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use LarAIgent\AiKit\Services\FeatureDetector;
use Throwable;

class DoctorCommand extends Command
{
    protected $signature = 'larai:doctor';
    protected $description = 'Check the health of all LarAIgent services';

    public function handle(FeatureDetector $features): int
    {
        $this->info('LarAIgent Health Check');
        $this->newLine();

        $hasError = false;

        // Database
        $db = config('database.default');
        try {
            DB::select('select 1');
            $this->ok("Database ({$db})");
        } catch (Throwable $e) {
            $this->printFail("Database ({$db})", $e->getMessage());
            $hasError = true;
        }

        // AI Provider
        $provider = $features->aiProvider();
        if ($features->aiProviderReady()) {
            $this->ok("AI Provider ({$provider})");
        } else {
            $this->printFail("AI Provider ({$provider})", 'missing API key');
            $hasError = true;
        }

        // Vector Store
        $driver = $features->vectorStoreDriver();
        if ($driver === 'none') {
            $this->skip('Vector Store', 'disabled (set to none)');
        } elseif ($driver === 'pinecone') {
            if ($features->pineconeReady()) {
                $this->ok('Vector Store (Pinecone)');
            } else {
                $this->printFail('Vector Store (Pinecone)', 'missing PINECONE_API_KEY or PINECONE_INDEX_HOST');
                $hasError = true;
            }
        } elseif ($driver === 'pgvector') {
            if ($features->pgvectorReady()) {
                $this->ok('Vector Store (pgvector)');
            } else {
                $this->printFail('Vector Store (pgvector)', 'pgsql + pgvector extension required');
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
            $this->skip("Queue ({$queueDriver})", 'sync mode — jobs run inline');
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

        return $hasError ? self::FAILURE : self::SUCCESS;
    }

    private function ok(string $name): void
    {
        $this->line("  <fg=green>[OK]</>      {$name}");
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
