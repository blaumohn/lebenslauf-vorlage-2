<?php

namespace App\Cli\Command;

use App\Cli\Cv\CvBuildService;
use EnvPipelineSpec\Env\Env;
use EnvPipelineSpec\Env\EnvCompiler;
use EnvPipelineSpec\Env\Context;
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

        $compiler = new EnvCompiler($this->rootPath());
        $buildContext = $this->resolveContext($compiler, $profile, 'build');
        if (!$this->validateEnv($compiler, $buildContext, $input, $output)) {
            return Command::FAILURE;
        }
        $runtimeContext = new Context($buildContext->pipeline(), 'runtime', $buildContext->profile());
        if (!$this->compileEnv($compiler, $runtimeContext, $input, $output)) {
            return Command::FAILURE;
        }

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

    private function resolveContext(EnvCompiler $compiler, string $profile, string $phase): Context
    {
        return $compiler->resolveContext([
            'pipeline' => 'dev',
            'phase' => $phase,
            'profile' => $profile,
        ]);
    }

    private function validateEnv(
        EnvCompiler $compiler,
        Context $context,
        InputInterface $input,
        OutputInterface $output
    ): bool {
        try {
            $compiler->validate($context, $input->isInteractive());
        } catch (\RuntimeException $exception) {
            $output->writeln('<error>' . $exception->getMessage() . '</error>');
            return false;
        }
        return true;
    }

    private function compileEnv(
        EnvCompiler $compiler,
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
