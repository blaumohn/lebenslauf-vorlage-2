<?php

namespace App\Cli\Command;

use App\Cli\Cv\CvBuildService;
use ConfigPipelineSpec\Config\Config;
use ConfigPipelineSpec\Config\ConfigCompiler;
use ConfigPipelineSpec\Config\Context;
use ConfigPipelineSpec\Config\ConfigSnapshot;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

#[AsCommand(name: 'build', description: 'Erstellt CSS und Lebenslauf-HTML.')]
final class BuildCommand extends BaseCommand
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
        $exitCode = $this->runCssBuild($output);
        if ($exitCode !== 0) {
            return $exitCode;
        }

        $compiler = new ConfigCompiler($this->rootPath());
        $buildContext = $this->resolveContext($compiler, 'build');
        $buildSnapshot = $this->resolveSnapshot($compiler, $buildContext, $input, $output);
        if ($buildSnapshot === null) {
            return Command::FAILURE;
        }
        $runtimeContext = new Context($buildContext->pipeline(), 'runtime', $buildContext->profile());
        if (!$this->compileEnv($compiler, $runtimeContext, $input, $output)) {
            return Command::FAILURE;
        }

        if (!$this->runCvBuild($buildSnapshot, $input, $output)) {
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function prepareBuildEnv(InputInterface $input): void
    {
        $this->applyAppEnvFromArg($input);
        $this->setPhaseEnv('build');
    }

    private function compileRuntimeEnv(
        ConfigCompiler $compiler,
        Context $buildContext,
        InputInterface $input,
        OutputInterface $output
    ): bool {
        $runtimeContext = new Context($buildContext->pipeline(), 'runtime', $buildContext->profile());
        return $this->compileEnv($compiler, $runtimeContext, $input, $output);
    }

    private function runCvBuild(ConfigSnapshot $snapshot, InputInterface $input, OutputInterface $output): bool
    {
        $env = new Config($this->rootPath(), $snapshot->values());
        $builder = new CvBuildService($env);

        try {
            $builder->build($output);
        } catch (\RuntimeException $exception) {
            $output->writeln('<error>' . $exception->getMessage() . '</error>');
            return false;
        }
        return true;
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

    private function compileEnv(
        ConfigCompiler $compiler,
        Context $context,
        InputInterface $input,
        OutputInterface $output
    ): bool {
        try {
            $compiler->compile($context, $input->isInteractive());
        } catch (\RuntimeException $exception) {
            $output->writeln('<error>' . $exception->getMessage() . '</error>');
            return false;
        }
        return true;
    }

    private function runCssBuild(OutputInterface $output): int
    {
        $process = new Process(['npm', 'run', 'build:css'], $this->rootPath());
        $process->run(function (string $type, string $buffer) use ($output): void {
            $output->write($buffer);
        });
        if ($process->isSuccessful()) {
            return Command::SUCCESS;
        }
        $output->writeln('<error>CSS build failed.</error>');
        return $process->getExitCode() ?? Command::FAILURE;
    }
}
