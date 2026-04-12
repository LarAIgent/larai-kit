<?php

namespace LarAIgent\AiKit\Services\Ingestion\Parsers;

use LarAIgent\AiKit\Contracts\DocumentParser;

class TextParser implements DocumentParser
{
    public function parse(string $path): string
    {
        $content = file_get_contents($path);

        if ($content === false) {
            return '';
        }

        return trim($content);
    }

    public function supportedMimeTypes(): array
    {
        return [
            'text/plain',
            'text/markdown',
            'text/csv',
        ];
    }

    public function supports(string $mimeType): bool
    {
        return in_array($mimeType, $this->supportedMimeTypes(), true);
    }
}
