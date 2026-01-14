<?php

namespace App\Http\Security;

use App\Http\Storage\FileStorage;

final class TokenService
{
    private FileStorage $storage;
    private string $dir;

    public function __construct(FileStorage $storage, string $dir)
    {
        $this->storage = $storage;
        $this->dir = rtrim($dir, DIRECTORY_SEPARATOR);
        $this->storage->ensureDir($this->dir);
    }

    public function verify(string $profile, string $token): bool
    {
        $hash = $this->hashToken($token);
        $list = $this->readHashes($profile);
        return in_array($hash, $list, true);
    }

    public function findProfileForToken(string $token): ?string
    {
        $hash = $this->hashToken($token);
        $files = glob($this->dir . DIRECTORY_SEPARATOR . '*.txt') ?: [];
        foreach ($files as $file) {
            $profile = basename($file, '.txt');
            $list = $this->readHashes($profile);
            if (in_array($hash, $list, true)) {
                return $profile;
            }
        }

        return null;
    }

    public function rotate(string $profile, array $plainTokens): void
    {
        $hashes = array_map(fn ($token) => $this->hashToken($token), $plainTokens);
        $content = implode("\n", $hashes) . "\n";
        $path = $this->tokenPath($profile);
        $this->storage->writeText($path, $content);
    }

    public function generateTokens(int $count): array
    {
        $tokens = [];
        for ($i = 0; $i < $count; $i++) {
            $tokens[] = bin2hex(random_bytes(16));
        }
        return $tokens;
    }

    public function readHashes(string $profile): array
    {
        $path = $this->tokenPath($profile);
        $content = $this->storage->readText($path);
        if ($content === null) {
            return [];
        }

        $lines = array_filter(array_map('trim', explode("\n", $content)));
        return array_values(array_unique($lines));
    }

    private function tokenPath(string $profile): string
    {
        return $this->dir . DIRECTORY_SEPARATOR . $profile . '.txt';
    }

    private function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }
}
