<?php

namespace App\Cli\Command;

use App\Cli\Cv\CvBuildService;
use App\Env\Env;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'cv build', description: 'Erstellt Lebenslauf-HTML aus YAML.')]
final class CvBuildCommand extends BaseCommand
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
        $env = new Env($this->rootPath());
        $builder = new CvBuildService($env);

        try {
            $builder->build($output, $input->isInteractive());
        } catch (\RuntimeException $exception) {
            $output->writeln('<error>' . $exception->getMessage() . '</error>');
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
