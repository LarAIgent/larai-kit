<?php

namespace LarAIgent\AiKit\Services\Ingestion\Parsers;

use LarAIgent\AiKit\Contracts\DocumentParser;
use Smalot\PdfParser\Parser;

class PdfParser implements DocumentParser
{
    public function parse(string $path): string
    {
        $parser = new Parser();
        $pdf = $parser->parseFile($path);

        return trim($pdf->getText());
    }

    public function supportedMimeTypes(): array
    {
        return [
            'application/pdf',
        ];
    }

    public function supports(string $mimeType): bool
    {
        return in_array($mimeType, $this->supportedMimeTypes(), true);
    }
}
