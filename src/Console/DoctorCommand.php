<?php

namespace LarAIgent\AiKit\Console;

use Illuminate\Console\Command;
use LarAIgent\AiKit\Services\HealthCheck;

class DoctorCommand extends Command
{
    protected $signature = 'larai:doctor {--deep : Run live API tests (embedding + vector store)}';
    protected $description = 'Check the health of all LarAI Kit services';

    public function handle(HealthCheck $healthCheck): int
    {
        $this->info('LarAI Kit Health Check');
        $this->newLine();

        $result = $healthCheck->run($this->option('deep'));

        $hasError = false;

        foreach ($result['checks'] as $name => $check) {
            $label = str_replace('_', ' ', ucfirst($name));
            $detail = $check['detail'] ?? null;
            $ms = isset($check['duration_ms']) ? "{$check['duration_ms']}ms" : null;
            $info = implode(', ', array_filter([$detail, $ms]));

            match ($check['status']) {
                'ok' => $this->ok($label, $info ?: null),
                'fail' => (function () use ($label, $info, &$hasError) {
                    $this->printFail($label, $info);
                    $hasError = true;
                })(),
                'warn' => $this->warn($label, $info),
                'skip' => $this->skip($label, $info),
            };
        }

        $config = $result['configuration'];
        $this->newLine();
        $this->line("<fg=white;options=bold>Configuration:</>");
        $this->line("  AI Provider:   " . ($config['ai_provider'] ?? '-'));
        $this->line("  Vector Store:  " . ($config['vector_store'] ?? '-'));
        $this->line("  Database:      " . ($config['database'] ?? '-'));
        $this->line("  Feature Tier:  " . ($config['tier'] ?? '-'));
        $this->line("  RAG:           " . (($config['rag_enabled'] ?? false) ? 'enabled' : 'disabled'));
        $this->line("  S3:            " . (($config['s3_enabled'] ?? false) ? 'enabled' : 'disabled'));

        if (! $this->option('deep')) {
            $this->newLine();
            $this->line('<fg=gray>  Tip: run with --deep to test live API calls (embedding + vector store)</>');
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

    private function warn(string $name, ?string $note = null): void
    {
        $msg = $note ? " — {$note}" : '';
        $this->line("  <fg=yellow>[WARN]</>    {$name}{$msg}");
    }

    private function skip(string $name, ?string $note = null): void
    {
        $msg = $note ? " — {$note}" : '';
        $this->line("  <fg=yellow>[SKIP]</>    {$name}{$msg}");
    }
}
