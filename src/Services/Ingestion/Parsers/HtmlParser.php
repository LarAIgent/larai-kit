<?php

namespace LarAIgent\AiKit\Services\Ingestion\Parsers;

use LarAIgent\AiKit\Contracts\DocumentParser;

class HtmlParser implements DocumentParser
{
    public function parse(string $pathOrContent): string
    {
        // Detect if input is a file path or raw HTML content
        if (file_exists($pathOrContent)) {
            $html = file_get_contents($pathOrContent);
        } else {
            $html = $pathOrContent;
        }

        if (empty($html)) {
            return '';
        }

        return $this->extractText($html);
    }

    protected function extractText(string $html): string
    {
        // Suppress DOM warnings on malformed HTML
        $prev = libxml_use_internal_errors(true);

        $dom = new \DOMDocument();
        $dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'), LIBXML_NOERROR | LIBXML_NOWARNING);

        libxml_use_internal_errors($prev);

        // Remove unwanted elements
        $this->removeElements($dom, ['script', 'style', 'nav', 'footer', 'header', 'aside', 'noscript', 'iframe']);

        // Try to find main content
        $content = $this->findMainContent($dom);

        if (empty(trim($content))) {
            // Fallback: use body text
            $body = $dom->getElementsByTagName('body');
            if ($body->length > 0) {
                $content = $body->item(0)->textContent;
            }
        }

        // Clean up whitespace
        $content = preg_replace('/[ \t]+/', ' ', $content);
        $content = preg_replace('/\n{3,}/', "\n\n", $content);

        return trim($content);
    }

    /**
     * Remove elements by tag name from the DOM.
     */
    protected function removeElements(\DOMDocument $dom, array $tagNames): void
    {
        foreach ($tagNames as $tag) {
            $elements = $dom->getElementsByTagName($tag);
            $remove = [];
            for ($i = 0; $i < $elements->length; $i++) {
                $remove[] = $elements->item($i);
            }
            foreach ($remove as $el) {
                $el->parentNode?->removeChild($el);
            }
        }
    }

    /**
     * Try to extract main content from article/main elements.
     */
    protected function findMainContent(\DOMDocument $dom): string
    {
        // Prefer <article>, then <main>, then [role="main"]
        foreach (['article', 'main'] as $tag) {
            $elements = $dom->getElementsByTagName($tag);
            if ($elements->length > 0) {
                return $elements->item(0)->textContent;
            }
        }

        // Try role="main"
        $xpath = new \DOMXPath($dom);
        $nodes = $xpath->query('//*[@role="main"]');
        if ($nodes && $nodes->length > 0) {
            return $nodes->item(0)->textContent;
        }

        return '';
    }

    public function supportedMimeTypes(): array
    {
        return [
            'text/html',
            'application/xhtml+xml',
        ];
    }

    public function supports(string $mimeType): bool
    {
        return in_array($mimeType, $this->supportedMimeTypes(), true);
    }
}
