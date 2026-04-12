<?php

namespace LarAIgent\AiKit\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MakeAgentCommand extends Command
{
    protected $signature = 'make:larai-agent {name : The name of the agent class}';
    protected $description = 'Create a new LarAIgent agent class';

    public function handle(Filesystem $files): int
    {
        $name = Str::studly($this->argument('name'));
        $path = app_path("Ai/Agents/{$name}.php");

        if ($files->exists($path)) {
            $this->error("Agent {$name} already exists at {$path}");
            return self::FAILURE;
        }

        $files->ensureDirectoryExists(dirname($path));

        $stub = <<<PHP
        <?php

        namespace App\Ai\Agents;

        use Laravel\Ai\Contracts\Agent;
        use Laravel\Ai\Contracts\HasTools;
        use Laravel\Ai\Promptable;

        class {$name} implements Agent, HasTools
        {
            use Promptable;

            public function instructions(): string
            {
                return 'You are a helpful AI assistant.';
            }

            public function tools(): iterable
            {
                return [
                    // Add tools here
                ];
            }
        }
        PHP;

        $files->put($path, $stub);

        $this->info("Agent created: {$path}");

        return self::SUCCESS;
    }
}
