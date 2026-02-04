<?php

namespace App\Cli\Shared;

use App\Cli\Command\BaseCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'python', description: 'Fuehrt ein Python-Skript ueber den CLI-Runner aus.')]
final class PythonCommand extends BaseCommand
{
    protected function configure(): void
    {
        $this->addArgument('pipeline', InputArgument::REQUIRED, 'Pipeline-Name')
            ->addArgument('script', InputArgument::REQUIRED, 'Relativer Pfad zum Skript.')
            ->addArgument('args', InputArgument::IS_ARRAY, 'Argumente fuer das Skript')
            ->addOption(
                'add-path',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Python-Pfade'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $pipeline = $this->resolvePipeline($input, $output);
        if ($pipeline === null) {
            return Command::FAILURE;
        }
        $script = $this->resolveScript($input, $output);
        if ($script === null) {
            return Command::FAILURE;
        }
        $runner = new PythonRunner($this->rootPath(), $this->configDir());
        return $runner->runWithContext(
            $pipeline,
            $script,
            $this->scriptArgs($input),
            $input->isInteractive(),
            $this->pythonPaths($input)
        );
    }

    private function resolvePipeline(InputInterface $input, OutputInterface $output): ?string
    {
        $value = $input->getArgument('pipeline');
        if (is_string($value)) {
            $value = trim($value);
        }
        if (!is_string($value) || $value === '') {
            $output->writeln('<error>Pipeline fehlt. Beispiel: dev</error>');
            return null;
        }
        return $value;
    }

    private function resolveScript(InputInterface $input, OutputInterface $output): ?string
    {
        $value = $input->getArgument('script');
        if (!is_string($value)) {
            $output->writeln('<error>Script-Pfad fehlt.</error>');
            return null;
        }
        $value = trim($value);
        if ($value === '') {
            $output->writeln('<error>Script-Pfad fehlt.</error>');
            return null;
        }
        return $value;
    }

    private function scriptArgs(InputInterface $input): array
    {
        $args = $input->getArgument('args');
        if (!is_array($args)) {
            return [];
        }
        $args = array_map('strval', $args);
        $filtered = array_filter($args, [$this, 'isNonEmptyString']);
        return array_values($filtered);
    }

    private function pythonPaths(InputInterface $input): array
    {
        $paths = $input->getOption('add-path');
        if (!is_array($paths)) {
            return [];
        }
        $paths = array_map('strval', $paths);
        $filtered = array_filter($paths, [$this, 'isNonEmptyString']);
        return array_values($filtered);
    }

    private function isNonEmptyString(string $value): bool
    {
        return trim($value) !== '';
    }
}
