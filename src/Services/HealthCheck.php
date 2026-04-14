<?php

namespace LarAIgent\AiKit\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use LarAIgent\AiKit\Contracts\EmbeddingProvider;
use Throwable;

class HealthCheck
{
    public function __construct(
        protected FeatureDetector $features,
    ) {}

    /**
     * Run all health checks.
     *
     * @return array{status: string, checks: array, configuration: array, timestamp: string}
     */
    public function run(bool $deep = false): array
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'ai_provider' => $this->checkAiProvider(),
            'vector_store' => $this->checkVectorStore(),
            'storage' => $this->checkStorage(),
            'cache' => $this->checkCache(),
            'redis' => $this->checkRedis(),
            'queue' => $this->checkQueue(),
        ];

        if ($deep && $this->features->aiProviderReady()) {
            $checks['embedding_probe'] = $this->checkEmbeddingProbe();
        }

        $hasError = collect($checks)->contains(fn ($c) => $c['status'] === 'fail');
        $hasDegraded = collect($checks)->contains(fn ($c) => $c['status'] === 'skip');

        return [
            'status' => $hasError ? 'unhealthy' : ($hasDegraded ? 'degraded' : 'healthy'),
            'checks' => $checks,
            'configuration' => $this->features->toArray(),
            'timestamp' => now()->toIso8601String(),
        ];
    }

    private function checkDatabase(): array
    {
        try {
            $start = microtime(true);
            DB::select('select 1');
            $ms = round((microtime(true) - $start) * 1000, 1);

            return ['status' => 'ok', 'detail' => config('database.default'), 'duration_ms' => $ms];
        } catch (Throwable $e) {
            return ['status' => 'fail', 'detail' => $e->getMessage()];
        }
    }

    private function checkAiProvider(): array
    {
        $provider = $this->features->aiProvider();

        if ($this->features->aiProviderReady()) {
            return ['status' => 'ok', 'detail' => $provider];
        }

        return ['status' => 'fail', 'detail' => "missing API key for {$provider}"];
    }

    private function checkEmbeddingProbe(): array
    {
        try {
            $embedder = app(EmbeddingProvider::class);
            $start = microtime(true);
            $vector = $embedder->embed('health check probe');
            $ms = round((microtime(true) - $start) * 1000);
            $dims = count($vector);
            $expected = $embedder->dimensions();

            if ($dims === $expected) {
                return ['status' => 'ok', 'detail' => "{$dims} dims", 'duration_ms' => $ms];
            }

            return ['status' => 'fail', 'detail' => "expected {$expected} dims, got {$dims}"];
        } catch (Throwable $e) {
            return ['status' => 'fail', 'detail' => $e->getMessage()];
        }
    }

    private function checkVectorStore(): array
    {
        $driver = $this->features->vectorStoreDriver();

        if ($driver === 'none') {
            return ['status' => 'skip', 'detail' => 'disabled'];
        }

        if ($this->features->vectorStoreReady()) {
            return ['status' => 'ok', 'detail' => $driver];
        }

        return ['status' => 'fail', 'detail' => "{$driver} not configured"];
    }

    private function checkStorage(): array
    {
        $disk = $this->features->storageDisk();

        try {
            Storage::disk($disk)->exists('larai');
            return ['status' => 'ok', 'detail' => $disk];
        } catch (Throwable $e) {
            return ['status' => 'fail', 'detail' => $e->getMessage()];
        }
    }

    private function checkCache(): array
    {
        try {
            Cache::put('larai_health', 'ok', 10);
            $ok = Cache::get('larai_health') === 'ok';

            return ['status' => $ok ? 'ok' : 'fail', 'detail' => config('cache.default')];
        } catch (Throwable $e) {
            return ['status' => 'fail', 'detail' => $e->getMessage()];
        }
    }

    private function checkRedis(): array
    {
        if (! $this->features->redisEnabled()) {
            return ['status' => 'skip', 'detail' => 'not configured'];
        }

        try {
            Redis::connection()->ping();
            return ['status' => 'ok'];
        } catch (Throwable $e) {
            return ['status' => 'fail', 'detail' => $e->getMessage()];
        }
    }

    private function checkQueue(): array
    {
        $driver = config('queue.default');

        if ($driver === 'sync') {
            return ['status' => 'skip', 'detail' => 'sync mode'];
        }

        return ['status' => 'ok', 'detail' => $driver];
    }
}
