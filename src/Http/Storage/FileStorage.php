<?php

namespace App\Http\Storage;

use App\Http\Storage\StorageException;

final class FileStorage
{
    public function ensureDir(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0775, true);
        }
    }

    public function readJson(string $path): ?array
    {
        if (!is_file($path)) {
            return null;
        }

        $content = file_get_contents($path);
        if ($content === false) {
            return null;
        }

        $data = json_decode($content, true);
        if (!is_array($data)) {
            return null;
        }

        return $data;
    }

    public function writeJson(string $path, array $data): void
    {
        $dir = dirname($path);
        $this->ensureDir($dir);

        $tmpPath = $path . '.tmp';
        $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        if (file_put_contents($tmpPath, $json) === false) {
            throw new StorageException("Failed to write temp file: {$tmpPath}");
        }
        if (!rename($tmpPath, $path)) {
            throw new StorageException("Failed to move temp file into place: {$path}");
        }
    }

    public function writeText(string $path, string $content): void
    {
        $dir = dirname($path);
        $this->ensureDir($dir);

        $tmpPath = $path . '.tmp';
        if (file_put_contents($tmpPath, $content) === false) {
            throw new StorageException("Failed to write temp file: {$tmpPath}");
        }
        if (!rename($tmpPath, $path)) {
            throw new StorageException("Failed to move temp file into place: {$path}");
        }
    }

    public function readText(string $path): ?string
    {
        if (!is_file($path)) {
            return null;
        }

        $content = file_get_contents($path);
        if ($content === false) {
            return null;
        }

        return $content;
    }

    public function delete(string $path): void
    {
        if (is_file($path)) {
            unlink($path);
        }
    }
}
