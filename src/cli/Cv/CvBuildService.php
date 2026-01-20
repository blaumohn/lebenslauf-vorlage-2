<?php

namespace App\Cli\Cv;

use App\Content\ContentConfig;
use ConfigPipelineSpec\Config\Config;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

final class CvBuildService
{
    private string $rootPath;
    private Config $env;
    private ContentConfig $content;
    private ContentSourceResolver $resolver;
    private CvUploadService $uploader;

    public function __construct(Config $env)
    {
        $this->env = $env;
        $this->rootPath = $env->rootPath();
        $this->content = new ContentConfig($this->rootPath);
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

    private function resolveTargets(): array
    {
        $defaultProfile = $this->defaultProfile();
        return $this->resolver->resolveTargets($defaultProfile, $interactive, $this->isAutomated());
    }

    private function buildTarget(array $target, string $jsonPath, OutputInterface $output, bool $interactive): void
    {
        $yamlPath = (string) ($target['yaml'] ?? '');
        $profile = (string) ($target['profile'] ?? '');
        $this->ensureFileExists($yamlPath, 'YAML');
        $this->runYamlToJson($yamlPath, $jsonPath);
        $this->uploader->upload($profile, $jsonPath, $output);
        $output->writeln("CV build completed: {$profile} ({$yamlPath})");
    }

    private function runYamlToJson(string $yamlPath, string $jsonPath): void
    {
        $data = $this->parseYaml($yamlPath);
        $json = $this->encodeJson($data);
        if (file_put_contents($jsonPath, $json) === false) {
            throw new \RuntimeException("JSON write failed: {$jsonPath}");
        }
    }

    private function parseYaml(string $path): array
    {
        try {
            $data = Yaml::parseFile($path);
        } catch (ParseException $error) {
            throw new \RuntimeException("YAML parse failed: {$path}", 0, $error);
        }
        if (!is_array($data)) {
            throw new \RuntimeException("YAML content is not a map: {$path}");
        }
        return $data;
    }

    private function encodeJson(array $data): string
    {
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            throw new \RuntimeException('JSON encode failed.');
        }
        return $json;
    }

    private function defaultProfile(): string
    {
        return $this->content->cvProfile();
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
