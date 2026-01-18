<?php

namespace App\Cli\Command;

use EnvPipelineSpec\Env\EnvInitializer;
use App\Content\ContentResolver;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Process\Process;

#[AsCommand(name: 'setup', description: 'Richtet die Entwicklungsumgebung ein.')]
final class SetupCommand extends BaseCommand
{
    protected function configure(): void
    {
        $this->addArgument('profile', InputArgument::REQUIRED, 'Profilname (z. B. dev)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $profile = $this->requireProfile($input, $output);
        if ($profile === null) {
            return Command::FAILURE;
        }

        $this->setProfileEnv($profile);
        $this->ensureLocalEnv($input);
        $this->ensureLocalContent($input);

        if (!$this->ensureVenv($output)) {
            return Command::FAILURE;
        }

        if (!$this->installNodeDependencies($input, $output)) {
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function ensureLocalEnv(InputInterface $input): void
    {
        $initializer = new EnvInitializer($this->rootPath());
        $initializer->ensureLocalEnv($input->isInteractive());
    }

    private function ensureLocalContent(InputInterface $input): void
    {
        $resolver = new ContentResolver($this->rootPath());
        $resolver->ensureLocalContent($input->isInteractive());
    }

    private function ensureVenv(OutputInterface $output): bool
    {
        $venvPath = Path::join($this->rootPath(), '.venv');
        if (is_dir($venvPath)) {
            return true;
        }
        $python = $this->findPython();
        if ($python === null) {
            $output->writeln('<error>Python 3 fehlt. Bitte installieren.</error>');
            return false;
        }
        return $this->runCommand([$python, '-m', 'venv', '.venv'], $output, false);
    }

    private function findPython(): ?string
    {
        $candidates = PHP_OS_FAMILY === 'Windows' ? ['python'] : ['python3', 'python'];
        foreach ($candidates as $candidate) {
            if ($this->isPython3($candidate)) {
                return $candidate;
            }
        }
        return null;
    }

    private function isPython3(string $binary): bool
    {
        $process = new Process([$binary, '-c', 'import sys; print(sys.version_info[0])']);
        $process->run();
        return $process->isSuccessful() && trim($process->getOutput()) === '3';
    }

    private function installNodeDependencies(InputInterface $input, OutputInterface $output): bool
    {
        return $this->runCommand(['npm', 'install'], $output, $input->isInteractive());
    }

    private function runCommand(array $command, OutputInterface $output, bool $interactive): bool
    {
        $process = new Process($command, $this->rootPath());
        if ($interactive && Process::isTtySupported()) {
            $process->setTty(true);
        }
        $process->run(function (string $type, string $buffer) use ($output): void {
            $output->write($buffer);
        });
        if (!$process->isSuccessful()) {
            $output->write($process->getErrorOutput());
        }
        return $process->isSuccessful();
    }
}
