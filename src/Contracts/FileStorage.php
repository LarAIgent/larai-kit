<?php

namespace LarAIgent\AiKit\Contracts;

use Illuminate\Http\UploadedFile;

interface FileStorage
{
    /**
     * Store a file and return its path on the disk.
     */
    public function store(UploadedFile $file, string $directory = ''): string;

    /**
     * Get a public URL for the given path.
     */
    public function url(string $path): string;

    /**
     * Delete a file at the given path.
     */
    public function delete(string $path): bool;

    /**
     * Get the contents of a file.
     */
    public function get(string $path): ?string;

    /**
     * Return the disk name being used.
     */
    public function disk(): string;
}
