<?php

namespace LarAIgent\AiKit\Services\Ingestion\Parsers;

use LarAIgent\AiKit\Contracts\DocumentParser;
use PhpOffice\PhpWord\Element\Text;
use PhpOffice\PhpWord\Element\TextBreak;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\IOFactory;

class DocxParser implements DocumentParser
{
    public function parse(string $path): string
    {
        $phpWord = IOFactory::load($path);
        $text = '';

        foreach ($phpWord->getSections() as $section) {
            $text .= $this->extractTextFromElements($section->getElements());
        }

        return trim($text);
    }

    /**
     * Recursively extract text from PhpWord elements.
     *
     * IMPORTANT: PhpWord's getText() can return a TextRun object instead of
     * a string for elements with mixed formatting. Never do naive string
     * concatenation — always check the return type.
     */
    protected function extractTextFromElements(iterable $elements): string
    {
        $text = '';

        foreach ($elements as $element) {
            if ($element instanceof TextBreak) {
                $text .= "\n";
            } elseif ($element instanceof TextRun) {
                $text .= $this->extractTextFromElements($element->getElements());
                $text .= "\n";
            } elseif ($element instanceof Text) {
                $value = $element->getText();
                $text .= is_string($value) ? $value : '';
            } elseif (method_exists($element, 'getElements')) {
                $text .= $this->extractTextFromElements($element->getElements());
                $text .= "\n";
            } elseif (method_exists($element, 'getText')) {
                $value = $element->getText();
                if (is_string($value)) {
                    $text .= $value;
                } elseif (is_object($value) && method_exists($value, 'getElements')) {
                    $text .= $this->extractTextFromElements($value->getElements());
                }
                $text .= "\n";
            }
        }

        return $text;
    }

    public function supportedMimeTypes(): array
    {
        return [
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ];
    }

    public function supports(string $mimeType): bool
    {
        return in_array($mimeType, $this->supportedMimeTypes(), true);
    }
}
