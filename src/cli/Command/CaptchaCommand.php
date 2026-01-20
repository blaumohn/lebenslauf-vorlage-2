<?php

namespace App\Cli\Command;

use ConfigPipelineSpec\Config\Config;
use ConfigPipelineSpec\Config\ConfigCompiler;
use ConfigPipelineSpec\Config\Context;
use ConfigPipelineSpec\Config\ConfigSnapshot;
use App\Http\Captcha\CaptchaService;
use App\Http\Storage\FileStorage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Path;

#[AsCommand(name: 'captcha', description: 'CAPTCHA-Tools (cleanup).')]
final class CaptchaCommand extends BaseCommand
{
    protected function configure(): void
    {
        $this->addArgument('action', InputArgument::REQUIRED, 'cleanup')
            ->addOption('app-env', null, InputOption::VALUE_REQUIRED, 'APP_ENV für die Ausführung setzen');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $action = strtolower(trim((string) $input->getArgument('action')));
        if ($action !== 'cleanup') {
            $output->writeln('<error>Usage: captcha cleanup</error>');
            return Command::FAILURE;
        }

        $appEnv = trim((string) $input->getOption('app-env'));
        if ($appEnv !== '') {
            $this->setProfileEnv($appEnv);
        }

        $profile = $appEnv;
        $this->setPhaseEnv('runtime');
        $compiler = new ConfigCompiler($this->rootPath());
        $context = $this->resolveContext($compiler, $profile, 'runtime');
        $snapshot = $this->resolveSnapshot($compiler, $context, $input, $output);
        if ($snapshot === null) {
            return Command::FAILURE;
        }

        try {
            $env = new Config($this->rootPath(), $snapshot->values());
        } catch (\RuntimeException $exception) {
            $output->writeln('<error>' . $exception->getMessage() . '</error>');
            return Command::FAILURE;
        }

        $service = $this->buildCaptchaService($env);
        $deleted = $service->cleanupExpired();
        $output->writeln("Deleted {$deleted} expired CAPTCHA files.");
        return Command::SUCCESS;
    }

    private function resolveContext(ConfigCompiler $compiler, string $profile, string $phase): Context
    {
        return $compiler->resolveContext([
            'pipeline' => 'dev',
            'phase' => $phase,
            'profile' => $profile !== '' ? $profile : null,
        ]);
    }

    private function resolveSnapshot(
        ConfigCompiler $compiler,
        Context $context,
        InputInterface $input,
        OutputInterface $output
    ): ?ConfigSnapshot {
        try {
            return $compiler->resolve($context);
        } catch (\RuntimeException $exception) {
            $output->writeln('<error>' . $exception->getMessage() . '</error>');
            return null;
        }
    }

    private function buildCaptchaService(Config $env): CaptchaService
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
