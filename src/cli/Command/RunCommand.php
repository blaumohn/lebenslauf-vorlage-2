<?php

namespace App\Cli\Command;

use App\Cli\PythonRunner;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'run', description: 'Startet den lokalen Dev-Server.')]
final class RunCommand extends BaseCommand
{
    protected function configure(): void
    {
        $this->addArgument('profile', InputArgument::REQUIRED, 'Profilname (z. B. dev)')
            ->addOption('env-file', null, InputOption::VALUE_REQUIRED, 'APP_ENV_FILE setzen')
            ->addOption('build', null, InputOption::VALUE_NONE, 'Vor dem Start cv build ausfuehren')
            ->addOption('mail-stdout', null, InputOption::VALUE_NONE, 'Mail-Ausgabe nach STDOUT');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $profile = $this->requireProfile($input, $output);
        if ($profile === null) {
            return Command::FAILURE;
        }
        if (strtolower($profile) !== 'dev') {
            $output->writeln('<error>run ist nur fuer das Profil dev erlaubt.</error>');
            return Command::FAILURE;
        }

        $this->setProfileEnv($profile);
        $runner = new PythonRunner($this->rootPath());
        $args = $this->devArgs($input);
        return $runner->run('src/cli/tools/dev.py', $args, $input->isInteractive());
    }

    private function devArgs(InputInterface $input): array
    {
        $args = [];
        $envFile = trim((string) $input->getOption('env-file'));
        if ($envFile !== '') {
            $args[] = '--env';
            $args[] = $envFile;
        }
        if ($input->getOption('build')) {
            $args[] = '--build';
        }
        if ($input->getOption('mail-stdout')) {
            $args[] = '--mail-stdout';
        }
        return $args;
    }
}
