<?php

namespace LarAIgent\AiKit\Contracts;

interface DocumentParser
{
    /**
     * Parse the given file content or path to plain text.
     */
    public function parse(string $path): string;

    /**
     * Return the list of mime types this parser supports.
     *
     * @return array<int, string>
     */
    public function supportedMimeTypes(): array;

    /**
     * Check whether this parser can handle the given mime type.
     */
    public function supports(string $mimeType): bool;
}
