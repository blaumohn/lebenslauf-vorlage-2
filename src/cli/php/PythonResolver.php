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

    public function findPythonCommand(): ?array
    {
        $configured = $this->configCommand('PYTHON_CMD');
        if ($configured === null) {
            return null;
        }
        return $configured;
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

    private function configCommand(string $key): ?array
    {
        $value = trim((string) ($this->configValues[$key] ?? ''));
        if ($value === '') {
            return null;
        }
        $parts = preg_split('/\\s+/', $value);
        if (!is_array($parts) || $parts === []) {
            return null;
        }
        $parts = array_values(array_filter($parts, static fn (string $part): bool => $part !== ''));
        if ($parts === []) {
            return null;
        }
        $parts[0] = $this->resolveBinaryPath($parts[0]);
        return $parts;
    }

    private function resolveBinaryPath(string $binary): string
    {
        if (Path::isAbsolute($binary)) {
            return $binary;
        }
        if (str_contains($binary, '/') || str_contains($binary, '\\')) {
            return Path::join($this->rootPath, $binary);
        }
        return $binary;
    }
}
