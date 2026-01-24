<?php

namespace App\Cli;

use Symfony\Component\Process\Process;

final class PythonRunner
{
    private PythonResolver $resolver;

    public function __construct(string $rootPath, array $configValues = [])
    {
        $this->resolver = new PythonResolver($rootPath, $configValues);
    }

    public function run(string $script, array $args = [], bool $interactive = false, bool $allowSystem = false): int
    {
        $python = $this->resolver->findPython($allowSystem);
        if ($python === null) {
            fwrite(STDERR, "Python 3 not found (python3 or python).\n");
            return 1;
        }

        $scriptPath = $this->resolver->scriptPath($script);
        $cmd = array_merge([$python, $scriptPath], $args);
        $process = new Process($cmd, $this->resolver->rootPath());
        if ($interactive && Process::isTtySupported()) {
            $process->setTty(true);
        }
        $process->run();
        if (!$process->isSuccessful()) {
            fwrite(STDERR, $process->getErrorOutput());
        }
        return (int) $process->getExitCode();
    }
}
