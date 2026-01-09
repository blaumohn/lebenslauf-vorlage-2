<?php

namespace App\Env;

final class EnvLoader
{
    public function loadDefaults(string $rootPath): void
    {
        $this->loadFile($rootPath . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'env-default.ini');
    }

    public function loadDeployDefaults(string $rootPath): void
    {
        $this->loadFile($rootPath . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'deploy-default.ini');
    }

    public function loadFile(string $path): void
    {
        if (!is_file($path)) {
            return;
        }

        $values = parse_ini_file($path, false, INI_SCANNER_RAW);
        if ($values === false) {
            return;
        }

        foreach ($values as $key => $value) {
            if (!is_string($key)) {
                continue;
            }
            if (getenv($key) !== false) {
                continue;
            }
            putenv($key . '=' . $value);
        }
    }

    public function exportDefaultsToFile(string $rootPath, string $targetPath): void
    {
        $this->exportFile($rootPath . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'env-default.ini', $targetPath);
    }

    public function exportDeployDefaultsToFile(string $rootPath, string $targetPath): void
    {
        $this->exportFile($rootPath . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'deploy-default.ini', $targetPath);
    }

    private function exportFile(string $path, string $targetPath): void
    {
        if (!is_file($path)) {
            return;
        }

        $values = parse_ini_file($path, false, INI_SCANNER_RAW);
        if ($values === false) {
            return;
        }

        $lines = [];
        foreach ($values as $key => $value) {
            if (!is_string($key)) {
                continue;
            }
            $lines[] = $key . '=' . $value;
        }

        if ($lines === []) {
            return;
        }

        file_put_contents($targetPath, implode(PHP_EOL, $lines) . PHP_EOL, FILE_APPEND);
    }
}
