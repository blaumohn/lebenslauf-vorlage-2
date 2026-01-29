<?php

namespace App\Cli;

use PipelineConfigSpec\PipelineConfigService;
use Symfony\Component\Process\Process;

final class PythonRunner
{
    private string $rootPath;

    public function __construct(string $rootPath)
    {
        $this->rootPath = rtrim($rootPath, DIRECTORY_SEPARATOR);
    }

    public function runWithContext(
        string $pipeline,
        string $script,
        array $args = [],
        bool $interactive = false,
        array $extraPaths = []
    ): int {
        $configValues = $this->loadConfigValues($pipeline);
        if ($configValues === null) {
            return 1;
        }
        $resolver = new PythonResolver($this->rootPath, $configValues);
        $command = $resolver->findPythonCommand();
        if ($command === null) {
            fwrite(STDERR, "PYTHON_CMD fehlt fuer {$pipeline}/python.\n");
            return 1;
        }

        $scriptPath = $resolver->scriptPath($script);
        $cmd = array_merge($command, [$scriptPath], $args);
        $env = $this->buildEnv($resolver, $configValues, $extraPaths);
        $process = new Process($cmd, $this->rootPath, $env);
        if ($interactive && Process::isTtySupported()) {
            $process->setTty(true);
        }
        $process->run();
        if (!$process->isSuccessful()) {
            fwrite(STDERR, $process->getErrorOutput());
        }
        return (int) $process->getExitCode();
    }

    private function loadConfigValues(string $pipeline): ?array
    {
        $pipelineSpec = new PipelineConfigService($this->rootPath);
        try {
            return $pipelineSpec->values($pipeline, 'python');
        } catch (\RuntimeException $exception) {
            fwrite(STDERR, $exception->getMessage() . "\n");
            return null;
        }
    }

    private function buildEnv(
        PythonResolver $resolver,
        array $configValues,
        array $extraPaths
    ): array
    {
        $paths = $this->normalizePaths($resolver, $extraPaths);
        $paths = array_merge(
            $paths,
            $this->configPaths($resolver, $configValues),
            $this->existingPaths($resolver)
        );
        $paths = $this->uniquePaths($paths);
        if ($paths === []) {
            return $_ENV;
        }
        $pythonPath = implode(PATH_SEPARATOR, $paths);
        $env = array_merge($_ENV, ['PYTHONPATH' => $pythonPath]);
        return $env;
    }

    private function normalizePaths(PythonResolver $resolver, array $paths): array
    {
        $normalized = [];
        foreach ($paths as $path) {
            $path = trim((string) $path);
            if ($path === '') {
                continue;
            }
            $normalized[] = $this->resolvePath($resolver, $path);
        }
        return $normalized;
    }

    private function resolvePath(PythonResolver $resolver, string $path): string
    {
        if ($path === '') {
            return $path;
        }
        if ($path[0] === '/' || $path[0] === '\\') {
            return $path;
        }
        if (str_contains($path, ':/')) {
            return $path;
        }
        return $resolver->scriptPath($path);
    }

    private function configPaths(PythonResolver $resolver, array $configValues): array
    {
        $value = trim((string) ($configValues['PYTHON_PATHS'] ?? ''));
        if ($value === '') {
            return [];
        }
        $parts = explode(PATH_SEPARATOR, $value);
        return $this->normalizePaths($resolver, $parts);
    }

    private function existingPaths(PythonResolver $resolver): array
    {
        $value = getenv('PYTHONPATH');
        if ($value === false || trim($value) === '') {
            return [];
        }
        $parts = explode(PATH_SEPARATOR, $value);
        return $this->normalizePaths($resolver, $parts);
    }

    private function uniquePaths(array $paths): array
    {
        $seen = [];
        $unique = [];
        foreach ($paths as $path) {
            if (isset($seen[$path])) {
                continue;
            }
            $seen[$path] = true;
            $unique[] = $path;
        }
        return $unique;
    }
}
