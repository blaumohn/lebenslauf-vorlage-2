<?php

namespace App\Cli\Command;

use App\Cli\Cv\CvUploadService;
use App\Env\Env;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'cv upload', description: 'Validiert JSON und rendert die Lebenslauf-HTML.')]
final class CvUploadCommand extends BaseCommand
{
    protected function configure(): void
    {
        $this->addArgument('profile', InputArgument::REQUIRED, 'CV-Profilname (z. B. default)')
            ->addArgument('json-path', InputArgument::REQUIRED, 'Pfad zur JSON-Datei')
            ->addOption('app-env', null, InputOption::VALUE_REQUIRED, 'APP_ENV fuer die Ausfuehrung setzen');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $appEnv = trim((string) $input->getOption('app-env'));
        if ($appEnv !== '') {
            $this->setProfileEnv($appEnv);
        }

        $env = new Env($this->rootPath());
        $service = new CvUploadService($env);

        $profile = trim((string) $input->getArgument('profile'));
        $jsonPath = trim((string) $input->getArgument('json-path'));
        if ($profile === '' || $jsonPath === '') {
            $output->writeln('<error>Usage: cv upload <PROFILE> <JSON_PATH></error>');
            return Command::FAILURE;
        }

        try {
            $service->upload($profile, $jsonPath, $output);
        } catch (\RuntimeException $exception) {
            $output->writeln('<error>' . $exception->getMessage() . '</error>');
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
