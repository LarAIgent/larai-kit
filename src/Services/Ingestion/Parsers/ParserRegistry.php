<?php

namespace LarAIgent\AiKit\Services\Ingestion\Parsers;

use LarAIgent\AiKit\Contracts\DocumentParser;
use RuntimeException;

class ParserRegistry
{
    /** @var array<int, DocumentParser> */
    protected array $parsers = [];

    public function __construct()
    {
        $this->parsers = [
            new TextParser(),
            new PdfParser(),
            new DocxParser(),
        ];
    }

    public function resolve(string $mimeType): DocumentParser
    {
        foreach ($this->parsers as $parser) {
            if ($parser->supports($mimeType)) {
                return $parser;
            }
        }

        throw new RuntimeException("No parser found for mime type: {$mimeType}");
    }

    public function supports(string $mimeType): bool
    {
        foreach ($this->parsers as $parser) {
            if ($parser->supports($mimeType)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, string>
     */
    public function allSupportedMimeTypes(): array
    {
        $types = [];
        foreach ($this->parsers as $parser) {
            $types = array_merge($types, $parser->supportedMimeTypes());
        }

        return array_unique($types);
    }
}
