<?php

namespace App;

use App\Env\EnvLoader;

final class Config
{
    private string $rootPath;
    private EnvLoader $loader;

    public function __construct(string $rootPath)
    {
        $this->rootPath = rtrim($rootPath, DIRECTORY_SEPARATOR);
        $this->loader = new EnvLoader();
        $this->loadEnvFiles();
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

    private function loadEnvFiles(): void
    {
        $override = getenv('APP_ENV_FILE');
        if ($override !== false && trim((string) $override) !== '') {
            $envPath = $this->resolvePath(trim((string) $override));
            $this->loader->loadFile($envPath);
            return;
        }

        $this->loader->loadDefaults($this->rootPath);

        $profile = (string) (getenv('APP_ENV') ?: 'dev');
        $commonPath = $this->rootPath . DIRECTORY_SEPARATOR . '.local' . DIRECTORY_SEPARATOR . 'env-common.ini';
        $profilePath = $this->rootPath . DIRECTORY_SEPARATOR . '.local' . DIRECTORY_SEPARATOR . 'env-' . $profile . '.ini';
        $this->loader->loadFile($commonPath);
        $this->loader->loadFile($profilePath);
    }

    private function resolvePath(string $path): string
    {
        if ($path === '') {
            return $path;
        }

        if ($path[0] === DIRECTORY_SEPARATOR) {
            return $path;
        }

        if ((bool) preg_match('/^[A-Za-z]:\\\\/', $path)) {
            return $path;
        }

        return $this->rootPath . DIRECTORY_SEPARATOR . $path;
    }

}
