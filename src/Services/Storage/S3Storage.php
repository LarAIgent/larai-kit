<?php

namespace LarAIgent\AiKit\Services\Storage;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use LarAIgent\AiKit\Contracts\FileStorage;

class S3Storage implements FileStorage
{
    public function store(UploadedFile $file, string $directory = ''): string
    {
        $dir = trim(config('larai-kit.storage_path', 'larai/documents') . '/' . $directory, '/');

        return Storage::disk('s3')->putFile($dir, $file);
    }

    public function url(string $path): string
    {
        return Storage::disk('s3')->url($path);
    }

    public function delete(string $path): bool
    {
        return Storage::disk('s3')->delete($path);
    }

    public function get(string $path): ?string
    {
        return Storage::disk('s3')->get($path);
    }

    public function disk(): string
    {
        return 's3';
    }
}
