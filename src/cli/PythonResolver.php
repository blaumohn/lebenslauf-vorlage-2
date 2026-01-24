<?php

namespace App\Cli;

use Symfony\Component\Filesystem\Path;
use Symfony\Component\Process\Process;

final class PythonResolver
{
    private string $rootPath;
    private array $configValues;

    public function __construct(string $rootPath, array $configValues = [])
    {
        $this->rootPath = rtrim($rootPath, DIRECTORY_SEPARATOR);
        $this->configValues = $configValues;
    }

    public function rootPath(): string
    {
        return $this->rootPath;
    }

    public function scriptPath(string $script): string
    {
        return Path::join($this->rootPath, ltrim($script, DIRECTORY_SEPARATOR));
    }

    public function createVenv(string $path, bool $interactive = false): bool
    {
        $target = Path::join($this->rootPath, ltrim($path, DIRECTORY_SEPARATOR));
        if (is_dir($target)) {
            return true;
        }
        $python = $this->findSystemPython();
        if ($python === null) {
            return false;
        }
        $process = new Process([$python, '-m', 'venv', $target], $this->rootPath);
        if ($interactive && Process::isTtySupported()) {
            $process->setTty(true);
        }
        $process->run();
        if (!$process->isSuccessful()) {
            fwrite(STDERR, $process->getErrorOutput());
        }
        return $process->isSuccessful();
    }

    public function findPython(bool $allowSystem = false): ?string
    {
        [$preferVenv, $allowSystemEnv, $promptOnMissingVenv] = $this->resolvePolicy();
        $allowSystem = $allowSystem || $allowSystemEnv;
        if ($preferVenv) {
            $venv = $this->findVenvPython();
            if ($venv !== null) {
                return $venv;
            }
            if ($promptOnMissingVenv && $this->isInteractive()) {
                fwrite(STDERR, "Missing .venv Python. Run: composer run setup\n");
            }
        }
        if (!$allowSystem) {
            return null;
        }
        return $this->findSystemPython();
    }

    private function findVenvPython(): ?string
    {
        $candidates = $this->venvPythonCandidates();
        foreach ($candidates as $candidate) {
            if (!is_file($candidate)) {
                continue;
            }
            if ($this->isPython3($candidate)) {
                return $candidate;
            }
        }
        return null;
    }

    private function venvPythonCandidates(): array
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return [
                Path::join($this->rootPath, '.venv', 'Scripts', 'python.exe'),
                Path::join($this->rootPath, '.venv', 'Scripts', 'python3.exe'),
            ];
        }
        return [
            Path::join($this->rootPath, '.venv', 'bin', 'python3'),
            Path::join($this->rootPath, '.venv', 'bin', 'python'),
        ];
    }

    private function findSystemPython(): ?string
    {
        $candidates = ['python3', 'python'];
        foreach ($candidates as $candidate) {
            $path = trim((string) $this->which($candidate));
            if ($path === '') {
                continue;
            }
            if ($this->isPython3($candidate)) {
                return $candidate;
            }
        }
        return null;
    }

    private function isPython3(string $binary): bool
    {
        $cmd = escapeshellarg($binary) . " -c " . escapeshellarg("import sys; print(sys.version_info[0])");
        $output = trim((string) shell_exec($cmd));
        return $output === '3';
    }

    private function which(string $binary): string
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return (string) shell_exec("where {$binary}");
        }
        return (string) shell_exec("command -v {$binary}");
    }

    private function resolvePolicy(): array
    {
        $preferVenv = $this->configFlag('PY_PREFER_VENV', true);
        $allowSystem = $this->configFlag('PY_ALLOW_SYSTEM', false);
        $promptOnMissingVenv = $this->configFlag('PY_PROMPT_ON_MISSING_VENV', true);
        return [$preferVenv, $allowSystem, $promptOnMissingVenv];
    }

    private function configFlag(string $key, bool $default): bool
    {
        $value = $this->configValues[$key] ?? null;
        if ($value === null || trim((string) $value) === '') {
            return $default;
        }
        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
    }

    private function isInteractive(): bool
    {
        if (function_exists('stream_isatty')) {
            return stream_isatty(STDIN) && stream_isatty(STDOUT);
        }
        if (function_exists('posix_isatty')) {
            return posix_isatty(STDIN) && posix_isatty(STDOUT);
        }
        return false;
    }
}
