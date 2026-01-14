<?php

namespace App\Cli\Command;

use App\Env\Env;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'env get', description: 'Gibt einen ENV-Wert aus.')]
final class EnvGetCommand extends BaseCommand
{
    protected function configure(): void
    {
        $this->addArgument('key', InputArgument::REQUIRED, 'ENV-Name')
            ->addOption('profile', null, InputOption::VALUE_REQUIRED, 'APP_ENV fuer die Ausfuehrung setzen');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $profile = trim((string) $input->getOption('profile'));
        if ($profile !== '') {
            $this->setProfileEnv($profile);
        }

        $key = trim((string) $input->getArgument('key'));
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
}
