<?php

namespace LarAIgent\AiKit\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MakeToolCommand extends Command
{
    protected $signature = 'make:larai-tool {name : The name of the tool class}';
    protected $description = 'Create a new LarAIgent tool class';

    public function handle(Filesystem $files): int
    {
        $name = Str::studly($this->argument('name'));
        $path = app_path("Ai/Tools/{$name}.php");

        if ($files->exists($path)) {
            $this->error("Tool {$name} already exists at {$path}");
            return self::FAILURE;
        }

        $files->ensureDirectoryExists(dirname($path));

        $stub = <<<PHP
        <?php

        namespace App\Ai\Tools;

        use Illuminate\Contracts\JsonSchema\JsonSchema;
        use Laravel\Ai\Contracts\Tool;
        use Laravel\Ai\Tools\Request;
        use Stringable;

        class {$name} implements Tool
        {
            public function description(): Stringable|string
            {
                return 'Describe what this tool does.';
            }

            public function handle(Request \$request): Stringable|string
            {
                // Implement tool logic here
                return 'Tool result';
            }

            public function schema(JsonSchema \$schema): array
            {
                return [
                    // Define parameters here
                ];
            }
        }
        PHP;

        $files->put($path, $stub);

        $this->info("Tool created: {$path}");

        return self::SUCCESS;
    }
}
