<?php

namespace App\Cli\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class BaseCommand extends Command
{
    protected function rootPath(): string
    {
        return dirname(__DIR__, 3);
    }

    protected function requireProfile(InputInterface $input, OutputInterface $output): ?string
    {
        $profile = trim((string) $input->getArgument('profile'));
        if ($profile !== '') {
            return $profile;
        }
        $output->writeln('<error>Profil fehlt. Beispiel: dev</error>');
        return null;
    }

    protected function setProfileEnv(string $profile): void
    {
        putenv('APP_ENV=' . $profile);
    }
}
