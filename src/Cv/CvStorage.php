<?php

namespace App\Cv;

use App\Storage\FileStorage;

final class CvStorage
{
    private FileStorage $storage;
    private string $cacheDir;

    public function __construct(FileStorage $storage, string $cacheDir)
    {
        $this->storage = $storage;
        $this->cacheDir = rtrim($cacheDir, DIRECTORY_SEPARATOR);
        $this->storage->ensureDir($this->cacheDir);
    }

    public function getPublicHtml(): ?string
    {
        return $this->storage->readText($this->publicPath());
    }

    public function getPrivateHtml(string $profile): ?string
    {
        return $this->storage->readText($this->privatePath($profile));
    }

    public function savePublicHtml(string $html): void
    {
        $this->storage->writeText($this->publicPath(), $html);
    }

    public function savePrivateHtml(string $profile, string $html): void
    {
        $this->storage->writeText($this->privatePath($profile), $html);
    }

    public function hasPublic(): bool
    {
        return is_file($this->publicPath());
    }

    private function publicPath(): string
    {
        return $this->cacheDir . DIRECTORY_SEPARATOR . 'cv-public.html';
    }

    private function privatePath(string $profile): string
    {
        return $this->cacheDir . DIRECTORY_SEPARATOR . 'cv-private-' . $profile . '.html';
    }
}
