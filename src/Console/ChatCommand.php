<?php

namespace LarAIgent\AiKit\Console;

use Illuminate\Console\Command;
use LarAIgent\AiKit\Services\Chat\ChatService;

class ChatCommand extends Command
{
    protected $signature = 'larai:chat';
    protected $description = 'Start an interactive chat session with the SupportAgent';

    public function handle(ChatService $chat): int
    {
        $this->info('LarAIgent Chat (type "exit" to quit)');
        $this->newLine();

        while (true) {
            $message = $this->ask('You');

            if ($message === null || strtolower(trim($message)) === 'exit') {
                $this->info('Goodbye!');
                break;
            }

            if (empty(trim($message))) {
                continue;
            }

            try {
                $result = $chat->sendMessage($message);
                $this->newLine();
                $this->line("<fg=cyan>Agent:</> {$result['reply']}");

                if (! empty($result['sources'])) {
                    $this->newLine();
                    $this->line('<fg=gray>Sources:</>');
                    foreach ($result['sources'] as $source) {
                        $this->line("  - {$source['name']}" . ($source['url'] ? " ({$source['url']})" : ''));
                    }
                }
                $this->newLine();
            } catch (\Throwable $e) {
                $this->error("Error: {$e->getMessage()}");
                $this->newLine();
            }
        }

        return self::SUCCESS;
    }
}
