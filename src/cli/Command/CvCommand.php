<?php

namespace App\Cli\Command;

use App\Cli\Cv\CvBuildService;
use App\Cli\Cv\CvUploadService;
use ConfigPipelineSpec\Config\ConfigCompiler;
use ConfigPipelineSpec\Config\Context;
use ConfigPipelineSpec\Config\Config;
use ConfigPipelineSpec\Config\ConfigSnapshot;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'cv', description: 'CV-Tools (build, upload).')]
final class CvCommand extends BaseCommand
{
    protected function configure(): void
    {
        $this->addArgument('action', InputArgument::REQUIRED, 'build oder upload')
            ->addArgument('profile', InputArgument::OPTIONAL, 'Profilname')
            ->addArgument('json', InputArgument::OPTIONAL, 'JSON-Pfad (bei upload)')
            ->addOption('app-env', null, InputOption::VALUE_REQUIRED, 'APP_ENV für die Ausführung setzen');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $action = strtolower(trim((string) $input->getArgument('action')));
        if ($action === 'build') {
            return $this->handleBuild($input, $output);
        }
        if ($action === 'upload') {
            return $this->handleUpload($input, $output);
        }

        $output->writeln('<error>Usage: cv build <PROFIL> | cv upload <PROFIL> <JSON></error>');
        return Command::FAILURE;
    }

    private function handleBuild(InputInterface $input, OutputInterface $output): int
    {
        $this->applyAppEnvFromArg($input);
        $this->setPhaseEnv('build');
        $compiler = new ConfigCompiler($this->rootPath());
        $context = $this->resolveContext($compiler, 'build');
        $snapshot = $this->resolveSnapshot($compiler, $context, $input, $output);
        if ($snapshot === null) {
            return Command::FAILURE;
        }
        $env = new Config($this->rootPath(), $snapshot->values());
        $builder = new CvBuildService($env);

        try {
            $builder->build($output, $input->isInteractive());
        } catch (\RuntimeException $exception) {
            $output->writeln('<error>' . $exception->getMessage() . '</error>');
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function handleUpload(InputInterface $input, OutputInterface $output): int
    {
        $appEnv = trim((string) $input->getOption('app-env'));
        if ($appEnv !== '') {
            $this->setProfileEnv($appEnv);
        }

        $profile = trim((string) $input->getArgument('profile'));
        $jsonPath = trim((string) $input->getArgument('json'));
        if ($profile === '' || $jsonPath === '') {
            $output->writeln('<error>Usage: cv upload <PROFIL> <JSON></error>');
            return Command::FAILURE;
        }

        $this->setPhaseEnv('build');
        $compiler = new ConfigCompiler($this->rootPath());
        $context = $this->resolveContext($compiler, 'build');
        $snapshot = $this->resolveSnapshot($compiler, $context, $input, $output);
        if ($snapshot === null) {
            return Command::FAILURE;
        }
        $env = new Config($this->rootPath(), $snapshot->values());
        $service = new CvUploadService($env);

        try {
            $service->upload($profile, $jsonPath, $output);
        } catch (\RuntimeException $exception) {
            $output->writeln('<error>' . $exception->getMessage() . '</error>');
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function resolveContext(ConfigCompiler $compiler, string $phase): Context
    {
        return $compiler->resolveContext([
            'pipeline' => 'dev',
            'phase' => $phase,
            'profile' => $profile,
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
}
