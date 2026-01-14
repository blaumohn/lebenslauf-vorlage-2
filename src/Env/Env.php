<?php

namespace App\Env;

final class Env
{
    private string $rootPath;

    public function __construct(string $rootPath)
    {
        $this->rootPath = rtrim($rootPath, DIRECTORY_SEPARATOR);
        $resolver = new EnvResolver($this->rootPath);
        $resolver->load();
    }

    public function rootPath(): string
    {
        return $this->rootPath;
    }

    public function get(string $key, mixed $default = null): mixed
    {
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
        $value = $this->get($key, null);
        if ($value === null || $value === '') {
            return $default;
        }
        return (int) $value;
    }

    public function basePath(): string
    {
        $value = trim((string) $this->get('APP_BASE_PATH', ''));
        if ($value === '' || $value === '/') {
            return '';
        }
        return '/' . trim($value, '/');
    }

}
