<?php

namespace App\Cli\Command;

use App\Env\Env;
use App\Http\Captcha\CaptchaService;
use App\Http\Storage\FileStorage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Path;

#[AsCommand(name: 'captcha cleanup', description: 'Loescht abgelaufene CAPTCHA-Dateien.')]
final class CaptchaCleanupCommand extends BaseCommand
{
    protected function configure(): void
    {
        $this->addOption('profile', null, InputOption::VALUE_REQUIRED, 'APP_ENV fuer die Ausfuehrung setzen');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $profile = trim((string) $input->getOption('profile'));
        if ($profile !== '') {
            $this->setProfileEnv($profile);
        }

        try {
            $env = new Env($this->rootPath());
        } catch (\RuntimeException $exception) {
            $output->writeln('<error>' . $exception->getMessage() . '</error>');
            return Command::FAILURE;
        }

        $service = $this->buildCaptchaService($env);
        $deleted = $service->cleanupExpired();
        $output->writeln("Deleted {$deleted} expired CAPTCHA files.");
        return Command::SUCCESS;
    }

    private function buildCaptchaService(Env $env): CaptchaService
    {
        $storage = new FileStorage();
        $path = Path::join($this->rootPath(), 'var', 'tmp', 'captcha');
        return new CaptchaService(
            $storage,
            $path,
            $env->getInt('CAPTCHA_TTL_SECONDS', 600)
        );
    }
}
