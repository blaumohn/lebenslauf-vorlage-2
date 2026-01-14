<?php

namespace App\Cli\Command;

use App\Cli\PythonRunner;
use App\Env\EnvResolver;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

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

        $runner = new PythonRunner($this->rootPath());
        return $runner->run('src/cli/tools/setup.py', [], $input->isInteractive(), true);
    }

    private function ensureLocalEnv(InputInterface $input): void
    {
        $resolver = new EnvResolver($this->rootPath());
        $resolver->ensureLocalEnv($input->isInteractive());
    }

}
