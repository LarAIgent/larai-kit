<?php

namespace LarAIgent\AiKit\Services\Storage;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use LarAIgent\AiKit\Contracts\FileStorage;

class LocalStorage implements FileStorage
{
    public function store(UploadedFile $file, string $directory = ''): string
    {
        $dir = trim(config('larai-kit.storage_path', 'larai/documents') . '/' . $directory, '/');

        return Storage::disk('public')->putFile($dir, $file);
    }

    public function url(string $path): string
    {
        return Storage::disk('public')->url($path);
    }

    public function delete(string $path): bool
    {
        return Storage::disk('public')->delete($path);
    }

    public function get(string $path): ?string
    {
        return Storage::disk('public')->get($path);
    }

    public function disk(): string
    {
        return 'public';
    }
}
