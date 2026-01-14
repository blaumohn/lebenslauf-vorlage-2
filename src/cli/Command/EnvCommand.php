<?php

namespace App\Cli\Command;

use App\Env\Env;
use App\Env\EnvLoader;
use App\Env\EnvPaths;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'env', description: 'ENV-Tools (get, export).')]
final class EnvCommand extends BaseCommand
{
    protected function configure(): void
    {
        $this->addArgument('action', InputArgument::REQUIRED, 'get oder export')
            ->addArgument('arg1', InputArgument::OPTIONAL, 'KEY oder MODE')
            ->addArgument('arg2', InputArgument::OPTIONAL, 'TARGET (bei export)')
            ->addOption('app-env', null, InputOption::VALUE_REQUIRED, 'APP_ENV fuer die Ausfuehrung setzen');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $action = strtolower(trim((string) $input->getArgument('action')));
        if ($action === 'get') {
            return $this->handleGet($input, $output);
        }
        if ($action === 'export') {
            return $this->handleExport($input, $output);
        }

        $output->writeln('<error>Usage: env get <KEY> | env export <MODE> <TARGET></error>');
        return Command::FAILURE;
    }

    private function handleGet(InputInterface $input, OutputInterface $output): int
    {
        $appEnv = trim((string) $input->getOption('app-env'));
        if ($appEnv !== '') {
            $this->setProfileEnv($appEnv);
        }

        $key = trim((string) $input->getArgument('arg1'));
        if ($key === '') {
            $output->writeln('<error>Usage: env get <KEY></error>');
            return Command::FAILURE;
        }

        try {
            $env = new Env($this->rootPath());
        } catch (\RuntimeException $exception) {
            $output->writeln('<error>' . $exception->getMessage() . '</error>');
            return Command::FAILURE;
        }

        $value = (string) $env->get($key, '');
        $output->write($value);
        return Command::SUCCESS;
    }

    private function handleExport(InputInterface $input, OutputInterface $output): int
    {
        $mode = trim((string) $input->getArgument('arg1'));
        $target = trim((string) $input->getArgument('arg2'));
        if ($mode === '' || $target === '') {
            $output->writeln('<error>Usage: env export <MODE> <TARGET></error>');
            return Command::FAILURE;
        }

        $paths = new EnvPaths($this->rootPath());
        $loader = new EnvLoader();

        if ($mode === 'defaults') {
            $loader->exportDefaultsToFile($paths, $target);
            return Command::SUCCESS;
        }
        if ($mode === 'deploy-defaults') {
            $loader->exportDeployDefaultsToFile($paths, $target);
            return Command::SUCCESS;
        }

        $output->writeln('<error>Unknown mode: ' . $mode . '</error>');
        return Command::FAILURE;
    }
}
