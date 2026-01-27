<?php

namespace App\Cli\Command;

use ConfigPipelineSpec\Config\ConfigCompiler;
use ConfigPipelineSpec\Config\Context;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class BaseCommand extends Command
{
    protected function rootPath(): string
    {
        return dirname(__DIR__, 4);
    }

    protected function requirePipeline(InputInterface $input, OutputInterface $output): ?string
    {
        $pipeline = $this->resolveStringArgument($input, 'pipeline');
        if ($pipeline !== null) {
            return $pipeline;
        }
        $output->writeln('<error>Pipeline fehlt. Beispiel: dev</error>');
        return null;
    }

    protected function resolveContext(
        ConfigCompiler $compiler,
        string $pipeline,
        string $phase
    ): Context {
        return $compiler->resolveContext([
            'pipeline' => $pipeline,
            'phase' => $phase,
        ]);
    }

    private function resolveStringArgument(InputInterface $input, string $name): ?string
    {
        $value = $input->getArgument($name);
        if (!is_string($value)) {
            return null;
        }
        $value = trim($value);
        return $value === '' ? null : $value;
    }
}
