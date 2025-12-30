<?php

namespace App;

final class Config
{
    private string $rootPath;
    private array $values;

    public function __construct(string $rootPath)
    {
        $this->rootPath = rtrim($rootPath, DIRECTORY_SEPARATOR);
        $this->values = [];
        $this->loadEnvFile();
    }

    public function rootPath(): string
    {
        return $this->rootPath;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $this->values)) {
            return $this->values[$key];
        }

        $envValue = getenv($key);
        if ($envValue !== false) {
            return $envValue;
        }

        return $default;
    }

    public function getBool(string $key, bool $default = false): bool
    {
        $value = $this->get($key, $default ? '1' : '0');
        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
    }

    public function getInt(string $key, int $default): int
    {
        $value = $this->get($key, $default);
        return (int) $value;
    }

    private function loadEnvFile(): void
    {
        $envPath = $this->rootPath . DIRECTORY_SEPARATOR . '.env';
        if (!is_file($envPath)) {
            return;
        }

        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || str_starts_with($trimmed, '#')) {
                continue;
            }

            $parts = explode('=', $trimmed, 2);
            if (count($parts) !== 2) {
                continue;
            }

            $key = trim($parts[0]);
            $value = trim($parts[1]);
            $value = trim($value, "\"'");

            $this->values[$key] = $value;
        }
    }
}
