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
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Path;

#[AsCommand(name: 'captcha', description: 'CAPTCHA-Tools (cleanup).')]
final class CaptchaCommand extends BaseCommand
{
    protected function configure(): void
    {
        $this->addArgument('pipeline', InputArgument::REQUIRED, 'Pipeline-Name')
            ->addArgument('action', InputArgument::OPTIONAL, 'cleanup', 'cleanup');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $action = strtolower(trim((string) $input->getArgument('action')));
        if ($action !== 'cleanup') {
            $output->writeln('<error>Usage: captcha <PIPELINE> [cleanup]</error>');
            return Command::FAILURE;
        }

        $pipeline = $this->requirePipeline($input, $output);
        if ($pipeline === null) {
            return Command::FAILURE;
        }
        $compiler = new ConfigCompiler($this->rootPath());
        $context = $this->resolveContext($compiler, $pipeline, 'runtime');
        $snapshot = $this->resolveSnapshot($compiler, $context, $input, $output);
        if ($snapshot === null) {
            return Command::FAILURE;
        }

        try {
            $config = new Config($this->rootPath(), $snapshot->values());
        } catch (\RuntimeException $exception) {
            $output->writeln('<error>' . $exception->getMessage() . '</error>');
            return Command::FAILURE;
        }

        $service = $this->buildCaptchaService($config);
        $deleted = $service->cleanupExpired();
        $output->writeln("Deleted {$deleted} expired CAPTCHA files.");
        return Command::SUCCESS;
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

    private function buildCaptchaService(Config $config): CaptchaService
    {
        $storage = new FileStorage();
        $path = Path::join($this->rootPath(), 'var', 'tmp', 'captcha');
        return new CaptchaService(
            $storage,
            $path,
            $config->getInt('CAPTCHA_TTL_SECONDS', 600)
        );
    }
}
