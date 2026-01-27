<?php

namespace App\Cli\Command;

use ConfigPipelineSpec\Config\ConfigCompiler;
use ConfigPipelineSpec\Config\Context;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'config', description: 'Config-Tools (get, show, lint, compile).')]
final class ConfigCommand extends BaseCommand
{
    protected function configure(): void
    {
        $this->addArgument('action', InputArgument::REQUIRED, 'get, show, lint oder compile')
            ->addArgument('pipeline', InputArgument::REQUIRED, 'Pipeline-Name')
            ->addArgument('arg1', InputArgument::OPTIONAL, 'KEY')
            ->addArgument('arg2', InputArgument::OPTIONAL, 'TARGET (bei compile)')
            ->addOption('phase', null, InputOption::VALUE_REQUIRED, 'Phase-Name');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $action = strtolower(trim((string) $input->getArgument('action')));
        if ($action === 'get') {
            return $this->handleGet($input, $output);
        }
        if ($action === 'show') {
            return $this->handleShow($input, $output);
        }
        if ($action === 'lint') {
            return $this->handleLint($input, $output);
        }
        if ($action === 'compile') {
            return $this->handleCompile($input, $output);
        }

        $output->writeln('<error>Usage: config <action> <PIPELINE> [ARGS]</error>');
        return Command::FAILURE;
    }

    private function handleGet(InputInterface $input, OutputInterface $output): int
    {
        $key = trim((string) $input->getArgument('arg1'));
        if ($key === '') {
            $output->writeln('<error>Usage: config get <PIPELINE> <KEY></error>');
            return Command::FAILURE;
        }

        $compiler = new ConfigCompiler($this->rootPath());
        $context = $this->resolveContextForCommand($compiler, $input, $output, 'runtime');
        if ($context === null) {
            return Command::FAILURE;
        }
        try {
            $snapshot = $compiler->validate($context);
        } catch (\RuntimeException $exception) {
            $output->writeln('<error>' . $exception->getMessage() . '</error>');
            return Command::FAILURE;
        }

        $value = (string) ($snapshot->values()[$key] ?? '');
        $output->write($value);
        return Command::SUCCESS;
    }

    private function handleShow(InputInterface $input, OutputInterface $output): int
    {
        $compiler = new ConfigCompiler($this->rootPath());
        $context = $this->resolveContextForCommand($compiler, $input, $output, 'runtime');
        if ($context === null) {
            return Command::FAILURE;
        }
        try {
            $snapshot = $compiler->validate($context);
        } catch (\RuntimeException $exception) {
            $output->writeln('<error>' . $exception->getMessage() . '</error>');
            return Command::FAILURE;
        }

        $output->writeln("Pipeline: {$context->pipeline()}");
        $output->writeln("Phase: {$context->phase()}");
        $output->writeln('Config-Dateien:');
        foreach ($snapshot->loadedFiles() as $file) {
            $output->writeln('- ' . $file);
        }
        $output->writeln('Werte:');
        foreach ($snapshot->values() as $key => $value) {
            $output->writeln($key . '=' . $value);
        }
        return Command::SUCCESS;
    }

    private function handleLint(InputInterface $input, OutputInterface $output): int
    {
        $compiler = new ConfigCompiler($this->rootPath());
        $context = $this->resolveContextForCommand($compiler, $input, $output, 'runtime');
        if ($context === null) {
            return Command::FAILURE;
        }
        try {
            $compiler->validate($context);
        } catch (\RuntimeException $exception) {
            $output->writeln('<error>' . $exception->getMessage() . '</error>');
            return Command::FAILURE;
        }
        $output->writeln('Config OK.');
        return Command::SUCCESS;
    }

    private function handleCompile(InputInterface $input, OutputInterface $output): int
    {
        $target = trim((string) $input->getArgument('arg2'));
        $targetPath = $target === '' ? null : $this->resolvePath($target);

        $compiler = new ConfigCompiler($this->rootPath());
        try {
            $context = $this->resolveContextForCommand($compiler, $input, $output, 'runtime');
            if ($context === null) {
                return Command::FAILURE;
            }
            $path = $compiler->compile($context, $targetPath);
        } catch (\RuntimeException $exception) {
            $output->writeln('<error>' . $exception->getMessage() . '</error>');
            return Command::FAILURE;
        }

        $output->writeln("Compiled config written: {$path}");
        return Command::SUCCESS;
    }

    private function resolveContextForCommand(
        ConfigCompiler $compiler,
        InputInterface $input,
        OutputInterface $output,
        string $phase
    ): ?Context
    {
        $pipeline = $this->requirePipeline($input, $output);
        if ($pipeline === null) {
            return null;
        }
        $requestedPhase = $this->resolveOptionString($input, 'phase');
        $contextPhase = $requestedPhase ?? $phase;

        return parent::resolveContext($compiler, $pipeline, $contextPhase);
    }

    private function resolveOptionString(InputInterface $input, string $name): ?string
    {
        $value = $input->getOption($name);
        if (!is_string($value)) {
            return null;
        }
        $value = trim($value);
        return $value === '' ? null : $value;
    }

    private function resolvePath(string $path): string
    {
        if ($path === '') {
            return $path;
        }
        if ($path[0] === DIRECTORY_SEPARATOR) {
            return $path;
        }
        if ((bool) preg_match('/^[A-Za-z]:\\\\/', $path)) {
            return $path;
        }
        return $this->rootPath() . DIRECTORY_SEPARATOR . $path;
    }
}
