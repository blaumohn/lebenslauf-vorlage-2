<?php

namespace App\Cli\Util;

use Symfony\Component\Filesystem\Path;

final class LocalConfigWriter
{
    private string $rootPath;

    public function __construct(string $rootPath)
    {
        $this->rootPath = rtrim($rootPath, DIRECTORY_SEPARATOR);
    }

    public function rotateIpSalt(string $pipeline): bool
    {
        $path = $this->runtimeConfigPath($pipeline);
        $content = $this->readFile($path);
        $salt = $this->generateSalt();
        $payload = $this->buildIpSaltPayload($content, $salt);
        return $this->writeFile($path, $payload);
    }

    private function runtimeConfigPath(string $pipeline): string
    {
        return Path::join($this->rootPath, '.local', $pipeline . '-runtime.yaml');
    }

    private function readFile(string $path): ?string
    {
        if (!is_file($path)) {
            return null;
        }
        $content = file_get_contents($path);
        return is_string($content) ? $content : null;
    }

    private function hasIpSalt(string $content): bool
    {
        return (bool) preg_match('/^IP_SALT\\s*:/m', $content);
    }

    private function generateSalt(): string
    {
        return bin2hex(random_bytes(16));
    }

    private function buildIpSaltPayload(?string $content, string $salt): string
    {
        $line = 'IP_SALT: ' . $salt;
        if ($content === null || trim($content) === '') {
            return $line . "\n";
        }
        if ($this->hasIpSalt($content)) {
            $updated = $this->replaceIpSaltLine($content, $line);
            return $this->ensureTrailingNewline($updated);
        }
        $merged = $this->appendLine($content, $line);
        return $this->ensureTrailingNewline($merged);
    }

    private function replaceIpSaltLine(string $content, string $line): string
    {
        return (string) preg_replace('/^IP_SALT\\s*:.*$/m', $line, $content);
    }

    private function appendLine(string $content, string $line): string
    {
        $suffix = substr($content, -1) === "\n" ? '' : "\n";
        return $content . $suffix . $line;
    }

    private function ensureTrailingNewline(string $content): string
    {
        return substr($content, -1) === "\n" ? $content : $content . "\n";
    }

    private function writeFile(string $path, string $content): bool
    {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        return file_put_contents($path, $content) !== false;
    }

}
