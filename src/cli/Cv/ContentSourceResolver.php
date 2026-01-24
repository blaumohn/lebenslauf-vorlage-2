<?php

namespace App\Cli\Cv;

use ConfigPipelineSpec\Config\Config;
use Symfony\Component\Filesystem\Path;

final class ContentSourceResolver
{
    private Config $env;

    public function __construct(Config $env)
    {
        $this->env = $env;
    }

    public function dataDir(): string
    {
        return $this->resolvePath($this->envValue('LEBENSLAUF_DATEN_PFAD'));
    }

    public function yamlPath(): string
    {
        $value = $this->envValue('LEBENSLAUF_YAML_PFAD');
        if ($value !== '') {
            return $this->resolvePath($value);
        }
        return $this->dataDir();
    }

    public function jsonPath(): string
    {
        $value = $this->envValue('LEBENSLAUF_JSON_PFAD');
        if ($value !== '') {
            return $this->resolvePath($value);
        }
        return $this->defaultJsonPath();
    }

    public function resolveTargets(string $defaultProfile, bool $interactive, bool $automated): array
    {
        $yamlPath = $this->requireYamlPath();
        $targets = $this->collectYamlTargets($yamlPath, $defaultProfile);
        if ($targets !== []) {
            return $targets;
        }
        if (!$this->isYamlDir($yamlPath)) {
            return $this->requireYamlTargets($yamlPath, $defaultProfile);
        }
        $targets = $this->resolveDemoTargets($yamlPath, $defaultProfile, $interactive, $automated);
        if ($targets !== []) {
            return $targets;
        }
        return $this->requireYamlTargets($yamlPath, $defaultProfile);
    }

    public function requireYamlPath(): string
    {
        $yamlPath = $this->yamlPath();
        if ($yamlPath === '') {
            throw new \RuntimeException('Missing config: LEBENSLAUF_DATEN_PFAD or LEBENSLAUF_YAML_PFAD');
        }
        return $yamlPath;
    }

    public function requireYamlTargets(string $yamlPath, string $defaultProfile): array
    {
        $targets = $this->collectYamlTargets($yamlPath, $defaultProfile);
        if ($targets !== []) {
            return $targets;
        }
        if ($this->isYamlDir($yamlPath)) {
            throw new \RuntimeException("No daten-*.yaml files found in: {$yamlPath}");
        }
        throw new \RuntimeException("YAML path not found: {$yamlPath}");
    }

    public function collectYamlTargets(string $yamlPath, string $defaultProfile): array
    {
        if ($yamlPath === '') {
            return [];
        }

        if (is_dir($yamlPath)) {
            return $this->collectYamlTargetsFromDir($yamlPath);
        }

        if (is_file($yamlPath)) {
            return [[
                'profile' => $defaultProfile,
                'yaml' => $yamlPath,
            ]];
        }

        return [];
    }

    private function collectYamlTargetsFromDir(string $yamlPath): array
    {
        $entries = scandir($yamlPath);
        if ($entries === false) {
            return [];
        }
        $targets = [];
        foreach ($entries as $entry) {
            $target = $this->targetFromEntry($yamlPath, $entry);
            if ($target !== null) {
                $targets[] = $target;
            }
        }
        return $targets;
    }

    private function targetFromEntry(string $yamlPath, mixed $entry): ?array
    {
        if (!is_string($entry)) {
            return null;
        }
        if (!preg_match('/^daten[-.](.+)\\.yaml$/i', $entry, $matches)) {
            return null;
        }
        return [
            'profile' => $matches[1],
            'yaml' => Path::join($yamlPath, $entry),
        ];
    }

    private function resolveDemoTargets(
        string $yamlPath,
        string $defaultProfile,
        bool $interactive,
        bool $automated
    ): array {
        if ($this->maybeCopyDemoData($yamlPath, $defaultProfile, $interactive, $automated)) {
            $targets = $this->collectYamlTargets($yamlPath, $defaultProfile);
            if ($targets !== []) {
                return $targets;
            }
        }
        return $this->fixtureTargetsIfEnabled($defaultProfile);
    }

    private function maybeCopyDemoData(
        string $yamlDir,
        string $defaultProfile,
        bool $interactive,
        bool $automated
    ): bool {
        if (!$this->shouldAttemptDemo($interactive, $automated)) {
            return false;
        }
        if ($this->shouldPrompt($interactive, $automated) && !$this->promptSetupDemo($yamlDir)) {
            return false;
        }
        $this->copyDemoData($yamlDir, $defaultProfile);
        return true;
    }

    private function fixtureTargetsIfEnabled(string $defaultProfile): array
    {
        if (!$this->env->getBool('AUTO_ENV_USE_FIXTURE', false)) {
            return [];
        }
        $fixturePath = $this->demoFixturePath();
        if (!is_file($fixturePath)) {
            throw new \RuntimeException("Demo fixture not found: {$fixturePath}");
        }
        return [[
            'profile' => $defaultProfile,
            'yaml' => $fixturePath,
        ]];
    }

    private function shouldAttemptDemo(bool $interactive, bool $automated): bool
    {
        return $interactive || $automated;
    }

    private function shouldPrompt(bool $interactive, bool $automated): bool
    {
        return $interactive && !$automated;
    }

    private function promptSetupDemo(string $yamlDir): bool
    {
        fwrite(STDOUT, "Keine daten-*.yaml in {$yamlDir} gefunden. Demo-Daten kopieren? [y/N] ");
        $answer = trim((string) fgets(STDIN));
        return strtolower($answer) === 'y';
    }

    private function copyDemoData(string $yamlDir, string $defaultProfile): void
    {
        $fixturePath = $this->demoFixturePath();
        $content = file_get_contents($fixturePath);
        if ($content === false) {
            throw new \RuntimeException("Failed to read demo fixture: {$fixturePath}");
        }
        if (!is_dir($yamlDir) && !mkdir($yamlDir, 0775, true) && !is_dir($yamlDir)) {
            throw new \RuntimeException("YAML directory missing and could not be created: {$yamlDir}");
        }
        $target = Path::join($yamlDir, 'daten-' . $defaultProfile . '.yaml');
        file_put_contents($target, $content);
        fwrite(STDOUT, "Demo-Daten kopiert: {$target}\n");
    }

    private function demoFixturePath(): string
    {
        return Path::join($this->env->rootPath(), 'tests', 'fixtures', 'lebenslauf', 'daten-gueltig.yaml');
    }

    private function defaultJsonPath(): string
    {
        return Path::join($this->env->rootPath(), 'var', 'tmp', 'lebenslauf.json');
    }

    private function envValue(string $key): string
    {
        return trim((string) $this->env->get($key, ''));
    }

    private function resolvePath(string $value): string
    {
        if ($value === '') {
            return $value;
        }
        if (Path::isAbsolute($value)) {
            return $value;
        }
        return Path::join($this->env->rootPath(), $value);
    }

    private function isYamlDir(string $yamlPath): bool
    {
        if ($yamlPath === $this->dataDir()) {
            return true;
        }
        return is_dir($yamlPath);
    }
}
