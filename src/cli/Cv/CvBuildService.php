<?php

namespace App\Cli\Cv;

use App\Cli\PythonRunner;
use App\Env\Env;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Path;

final class CvBuildService
{
    private string $rootPath;
    private Env $env;
    private PythonRunner $python;
    private ContentSourceResolver $resolver;
    private CvUploadService $uploader;

    public function __construct(Env $env)
    {
        $this->env = $env;
        $this->rootPath = $env->rootPath();
        $this->python = new PythonRunner($this->rootPath);
        $this->resolver = new ContentSourceResolver($env);
        $this->uploader = new CvUploadService($env);
    }

    public function build(OutputInterface $output, bool $interactive): void
    {
        $targets = $this->resolveTargets($interactive);
        $jsonPath = $this->resolver->jsonPath();
        $this->ensureDir(dirname($jsonPath));

        foreach ($targets as $target) {
            $this->buildTarget($target, $jsonPath, $output, $interactive);
        }
    }

    private function resolveTargets(bool $interactive): array
    {
        $defaultProfile = $this->defaultProfile();
        return $this->resolver->resolveTargets($defaultProfile, $interactive, $this->isAutomated());
    }

    private function buildTarget(array $target, string $jsonPath, OutputInterface $output, bool $interactive): void
    {
        $yamlPath = (string) ($target['yaml'] ?? '');
        $profile = (string) ($target['profile'] ?? '');
        $this->ensureFileExists($yamlPath, 'YAML');
        $this->runYamlToJson($yamlPath, $jsonPath, $interactive);
        $this->uploader->upload($profile, $jsonPath, $output);
        $output->writeln("CV build completed: {$profile} ({$yamlPath})");
    }

    private function runYamlToJson(string $yamlPath, string $jsonPath, bool $interactive): void
    {
        $script = Path::join('src', 'cli', 'tools', 'yaml_to_json.py');
        $exitCode = $this->python->run($script, [$yamlPath, $jsonPath], $interactive);
        if ($exitCode !== 0) {
            throw new \RuntimeException('YAML to JSON conversion failed.');
        }
    }

    private function defaultProfile(): string
    {
        return (string) $this->env->get('CV_PROFILE', $this->env->get('DEFAULT_CV_PROFILE', 'default'));
    }

    private function isAutomated(): bool
    {
        return getenv('AUTO_ENV_SETUP') === '1';
    }

    private function ensureDir(string $path): void
    {
        if (is_dir($path)) {
            return;
        }
        if (!mkdir($path, 0775, true) && !is_dir($path)) {
            throw new \RuntimeException("JSON output directory missing and could not be created: {$path}");
        }
    }

    private function ensureFileExists(string $path, string $label): void
    {
        if (!is_file($path)) {
            throw new \RuntimeException("{$label} not found: {$path}");
        }
    }
}
