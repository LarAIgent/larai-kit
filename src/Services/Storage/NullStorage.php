<?php

namespace LarAIgent\AiKit\Services\Storage;

use Illuminate\Http\UploadedFile;
use LarAIgent\AiKit\Contracts\FileStorage;

class NullStorage implements FileStorage
{
    public function store(UploadedFile $file, string $directory = ''): string
    {
        return '';
    }

    public function url(string $path): string
    {
        return '';
    }

    public function delete(string $path): bool
    {
        return true;
    }

    public function get(string $path): ?string
    {
        return null;
    }

    public function disk(): string
    {
        return 'null';
    }
}
