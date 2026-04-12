<?php

namespace LarAIgent\AiKit\Console;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'larai:install';
    protected $description = 'Install LarAIgent: publish config, run migrations, create storage link';

    public function handle(): int
    {
        $this->info('Installing LarAIgent...');

        $this->call('vendor:publish', [
            '--tag' => 'larai-kit-config',
        ]);

        $this->call('migrate');

        if (! file_exists(public_path('storage'))) {
            $this->call('storage:link');
        }

        $this->info('LarAIgent installed successfully.');
        $this->newLine();
        $this->line('Next steps:');
        $this->line('  1. Set OPENAI_API_KEY in your .env');
        $this->line('  2. Run <comment>php artisan larai:doctor</comment> to check service health');
        $this->line('  3. Visit <comment>/larai</comment> to use the chat UI');

        return self::SUCCESS;
    }
}
