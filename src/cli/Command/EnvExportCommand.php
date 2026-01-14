<?php

namespace App\Cli\Command;

use App\Env\EnvLoader;
use App\Env\EnvPaths;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'env export', description: 'Exportiert ENV-Defaults in eine Datei.')]
final class EnvExportCommand extends BaseCommand
{
    protected function configure(): void
    {
        $this->addArgument('mode', InputArgument::REQUIRED, 'defaults oder deploy-defaults')
            ->addArgument('target', InputArgument::REQUIRED, 'Zielpfad fuer den Export');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $mode = trim((string) $input->getArgument('mode'));
        $target = trim((string) $input->getArgument('target'));
        if ($mode === '' || $target === '') {
            $output->writeln('<error>Usage: env export <mode> <target></error>');
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
