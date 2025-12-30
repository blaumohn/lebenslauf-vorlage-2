<?php

namespace App\Security;

use App\Storage\FileStorage;

final class RateLimiter
{
    private FileStorage $storage;
    private string $dir;

    public function __construct(FileStorage $storage, string $dir)
    {
        $this->storage = $storage;
        $this->dir = rtrim($dir, DIRECTORY_SEPARATOR);
        $this->storage->ensureDir($this->dir);
    }

    public function allow(string $key, int $max, int $windowSeconds): bool
    {
        $path = $this->dir . DIRECTORY_SEPARATOR . $this->safeKey($key) . '.json';
        $now = time();
        $data = $this->storage->readJson($path) ?? ['timestamps' => []];
        $timestamps = array_filter($data['timestamps'] ?? [], fn ($ts) => is_int($ts));

        $filtered = [];
        foreach ($timestamps as $timestamp) {
            if ($timestamp >= ($now - $windowSeconds)) {
                $filtered[] = $timestamp;
            }
        }

        if (count($filtered) >= $max) {
            return false;
        }

        $filtered[] = $now;
        $this->storage->writeJson($path, ['timestamps' => array_values($filtered)]);
        return true;
    }

    private function safeKey(string $key): string
    {
        return preg_replace('/[^a-zA-Z0-9_-]/', '_', $key);
    }
}
