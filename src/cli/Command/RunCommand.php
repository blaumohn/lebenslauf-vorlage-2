<?php

namespace App\Cli\Command;

use App\Cli\PythonRunner;
use EnvPipelineSpec\Env\EnvCompiler;
use EnvPipelineSpec\Env\Context;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'run', description: 'Startet den lokalen Dev-Server.')]
final class RunCommand extends BaseCommand
{
    protected function configure(): void
    {
        $this->addArgument('profile', InputArgument::REQUIRED, 'Profilname (z. B. dev)')
            ->addOption('build', null, InputOption::VALUE_NONE, 'Vor dem Start cv build ausführen')
            ->addOption('mail-stdout', null, InputOption::VALUE_NONE, 'Mail-Ausgabe nach STDOUT');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $profile = $this->requireProfile($input, $output);
        if ($profile === null) {
            return Command::FAILURE;
        }
        if (strtolower($profile) !== 'dev') {
            $output->writeln('<error>run ist nur für das Profil dev erlaubt.</error>');
            return Command::FAILURE;
        }

        $this->setProfileEnv($profile);
        $compiler = new EnvCompiler($this->rootPath());
        $runtimeContext = $this->resolveContext($compiler, $profile, 'runtime');
        if (!$this->compileEnv($compiler, $runtimeContext, $input, $output)) {
            return Command::FAILURE;
        }
        $runner = new PythonRunner($this->rootPath());
        $args = $this->devArgs($input);
        return $runner->run('src/cli/tools/dev.py', $args, $input->isInteractive());
    }

    private function devArgs(InputInterface $input): array
    {
        $args = [];
        if ($input->getOption('build')) {
            $args[] = '--build';
        }
        if ($input->getOption('mail-stdout')) {
            $args[] = '--mail-stdout';
        }
        return $args;
    }

    private function resolveContext(EnvCompiler $compiler, string $profile, string $phase): Context
    {
        return $compiler->resolveContext([
            'pipeline' => 'dev',
            'phase' => $phase,
            'profile' => $profile,
        ]);
    }

    private function compileEnv(
        EnvCompiler $compiler,
        Context $context,
        InputInterface $input,
        OutputInterface $output
    ): bool
    {
        try {
            $compiler->compile($context, $input->isInteractive());
        } catch (\RuntimeException $exception) {
            $output->writeln('<error>' . $exception->getMessage() . '</error>');
            return false;
        }
        return true;
    }
}
