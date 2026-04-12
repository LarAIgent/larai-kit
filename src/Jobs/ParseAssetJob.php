<?php

namespace LarAIgent\AiKit\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use LarAIgent\AiKit\Models\Asset;
use LarAIgent\AiKit\Models\Ingestion;
use LarAIgent\AiKit\Services\Ingestion\Parsers\ParserRegistry;
use Throwable;

class ParseAssetJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly Asset $asset,
        public readonly Ingestion $ingestion,
    ) {}

    public function handle(ParserRegistry $registry): void
    {
        $this->ingestion->markState('parsing');

        try {
            $mime = $this->asset->mime ?? 'text/plain';

            if (! $registry->supports($mime)) {
                $this->ingestion->markState('failed', "Unsupported mime type: {$mime}");
                return;
            }

            $parser = $registry->resolve($mime);

            // Resolve file path from storage
            $disk = $this->asset->source_disk;
            $path = $this->asset->source_path;

            if ($disk && $path) {
                $fullPath = \Illuminate\Support\Facades\Storage::disk($disk)->path($path);
            } else {
                $this->ingestion->markState('failed', 'No file path available for parsing.');
                return;
            }

            $text = $parser->parse($fullPath);

            if (empty(trim($text))) {
                $this->ingestion->markState('failed', 'Parser returned empty text.');
                return;
            }

            // Store parsed text on the Document record too
            $this->asset->update(['parsed_text' => $text]);

            ChunkAssetJob::dispatch($this->asset, $this->ingestion, $text);

        } catch (Throwable $e) {
            $this->ingestion->markState('failed', $e->getMessage());
            throw $e;
        }
    }
}
