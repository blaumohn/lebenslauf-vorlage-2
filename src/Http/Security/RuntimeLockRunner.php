<?php

namespace App\Http\Security;

use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\FlockStore;

final class RuntimeLockRunner
{
    private string $lockDir;

    public function __construct(string $lockDir)
    {
        $this->lockDir = rtrim($lockDir, DIRECTORY_SEPARATOR);
    }

    public function runWithLock(string $key, callable $operation): mixed
    {
        $normalizedKey = $this->normalizeKey($key);
        if ($this->canUseSymfonyLock()) {
            $result = $this->runWithSymfonyLock($normalizedKey, $operation);
            return $result;
        }
        $result = $this->runWithFileLock($normalizedKey, $operation);
        return $result;
    }

    private function canUseSymfonyLock(): bool
    {
        if (!class_exists(FlockStore::class)) {
            return false;
        }
        return class_exists(LockFactory::class);
    }

    private function runWithSymfonyLock(string $key, callable $operation): mixed
    {
        $this->ensureDir($this->lockDir);
        $store = new FlockStore($this->lockDir);
        $factory = new LockFactory($store);
        $lock = $factory->createLock($key);
        if (!$lock->acquire(true)) {
            throw new \RuntimeException("Lock konnte nicht gesetzt werden: {$key}");
        }
        try {
            $result = $operation();
            return $result;
        } finally {
            $lock->release();
        }
    }

    private function runWithFileLock(string $key, callable $operation): mixed
    {
        $this->ensureDir($this->lockDir);
        $path = $this->lockPath($key);
        $handle = fopen($path, 'c');
        if ($handle === false) {
            throw new \RuntimeException("Lock-Datei konnte nicht geoeffnet werden: {$path}");
        }
        if (!@flock($handle, LOCK_EX)) {
            fclose($handle);
            throw new \RuntimeException("Lock konnte nicht gesetzt werden: {$key}");
        }
        try {
            $result = $operation();
            return $result;
        } finally {
            @flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    private function normalizeKey(string $key): string
    {
        $value = preg_replace('/[^a-zA-Z0-9_-]/', '_', $key);
        if (is_string($value) && $value !== '') {
            return $value;
        }
        throw new \RuntimeException('Lock-Key ist ungueltig.');
    }

    private function ensureDir(string $dir): void
    {
        if (is_dir($dir)) {
            return;
        }
        if (@mkdir($dir, 0775, true) || is_dir($dir)) {
            return;
        }
        throw new \RuntimeException("Lock-Verzeichnis fehlt: {$dir}");
    }

    private function lockPath(string $key): string
    {
        return $this->lockDir . DIRECTORY_SEPARATOR . $key . '.lock';
    }
}
