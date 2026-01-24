<?php

namespace App\Cli\Command;

use App\Cli\PythonRunner;
use ConfigPipelineSpec\Config\ConfigCompiler;
use ConfigPipelineSpec\Config\Context;
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
        $this->addArgument('pipeline', InputArgument::REQUIRED, 'Pipeline-Name')
            ->addOption('build', null, InputOption::VALUE_NONE, 'Vor dem Start cv build ausfuehren')
            ->addOption('demo', null, InputOption::VALUE_NONE, 'Demo-Daten verwenden')
            ->addOption('mail-stdout', null, InputOption::VALUE_NONE, 'Mail-Ausgabe nach STDOUT');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $pipeline = $this->requirePipeline($input, $output);
        if ($pipeline === null) {
            return Command::FAILURE;
        }
        if (strtolower($pipeline) !== 'dev') {
            $output->writeln('<error>run ist nur fuer die Pipeline dev erlaubt.</error>');
            return Command::FAILURE;
        }
        $compiler = new ConfigCompiler($this->rootPath());
        $runtimeContext = $this->resolveContext($compiler, $pipeline, 'runtime');
        $snapshot = $this->resolveRuntimeSnapshot($compiler, $runtimeContext, $input, $output);
        if ($snapshot === null) {
            return Command::FAILURE;
        }
        $setupValues = $this->resolveSetupConfigValues($compiler, $pipeline, $input, $output);
        $runner = new PythonRunner($this->rootPath(), $setupValues);
        $args = $this->devArgs($input, $pipeline);
        return $runner->run('src/cli/tools/dev.py', $args, $input->isInteractive());
    }

    private function devArgs(InputInterface $input, string $pipeline): array
    {
        $args = ['--pipeline', $pipeline];
        if ($input->getOption('build')) {
            $args[] = '--build';
        }
        if ($input->getOption('demo')) {
            $args[] = '--demo';
        }
        if ($input->getOption('mail-stdout')) {
            $args[] = '--mail-stdout';
        }
        return $args;
    }

    private function resolveRuntimeSnapshot(
        ConfigCompiler $compiler,
        Context $context,
        InputInterface $input,
        OutputInterface $output
    ): ?\ConfigPipelineSpec\Config\ConfigSnapshot
    {
        try {
            $snapshot = $compiler->validate($context, $input->isInteractive());
            $compiler->compile($context, $input->isInteractive());
            return $snapshot;
        } catch (\RuntimeException $exception) {
            $output->writeln('<error>' . $exception->getMessage() . '</error>');
            return null;
        }
    }

    private function resolveSetupConfigValues(
        ConfigCompiler $compiler,
        string $pipeline,
        InputInterface $input,
        OutputInterface $output
    ): array {
        $context = $compiler->resolveContext([
            'pipeline' => $pipeline,
            'phase' => 'setup',
        ]);
        try {
            $snapshot = $compiler->validate($context, $input->isInteractive());
            return $snapshot->values();
        } catch (\RuntimeException $exception) {
            $output->writeln('<error>' . $exception->getMessage() . '</error>');
            return [];
        }
    }
}
