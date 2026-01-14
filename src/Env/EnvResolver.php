<?php

namespace App\Env;

final class EnvResolver
{
    private string $rootPath;
    private EnvLoader $loader;
    private EnvPaths $paths;

    public function __construct(string $rootPath, ?EnvLoader $loader = null)
    {
        $this->rootPath = rtrim($rootPath, DIRECTORY_SEPARATOR);
        $this->loader = $loader ?? new EnvLoader();
        $this->paths = new EnvPaths($this->rootPath);
    }

    public function load(): void
    {
        $override = $this->readOverride();
        if ($override !== null) {
            $path = $this->paths->resolvePath($override);
            $this->loader->loadFile($path);
            return;
        }

        $this->loader->loadDefaults($this->rootPath);
        $this->loadLocalEnv();
    }

    public function ensureLocalEnv(bool $interactive): void
    {
        $profile = $this->requireProfileEnv();
        $localDir = $this->paths->localDir();
        $commonPath = $this->paths->localCommonFile();
        $profilePath = $this->paths->localProfileFile($profile);
        $fixturePath = $this->paths->demoFixtureFile();
        $automated = $this->isAutomated();

        if ($this->envFilesExist($commonPath, $profilePath)) {
            return;
        }

        if (!$this->shouldAttemptSetup($automated, $interactive)) {
            return;
        }

        if (!$this->shouldSetupDemo($automated, $interactive)) {
            return;
        }

        $this->copyDemoEnv($fixturePath, $localDir, $profilePath);
    }

    private function envFilesExist(string $commonPath, string $profilePath): bool
    {
        return is_file($commonPath) || is_file($profilePath);
    }

    private function loadLocalEnv(): void
    {
        $profile = $this->requireProfileEnv();
        $commonPath = $this->paths->localCommonFile();
        $profilePath = $this->paths->localProfileFile($profile);
        $this->loader->loadFile($commonPath);
        $this->loader->loadFile($profilePath);
    }

    private function readOverride(): ?string
    {
        $override = getenv('APP_ENV_FILE');
        if ($override === false) {
            return null;
        }
        $override = trim((string) $override);
        return $override === '' ? null : $override;
    }

    private function requireProfileEnv(): string
    {
        $profile = getenv('APP_ENV');
        if ($profile === false) {
            throw new \RuntimeException('APP_ENV is required.');
        }
        $profile = (string) $profile;
        if ($profile === '') {
            throw new \RuntimeException('APP_ENV is empty.');
        }
        return $profile;
    }

    private function isAutomated(): bool
    {
        return getenv('AUTO_ENV_SETUP') === '1';
    }

    private function shouldAttemptSetup(bool $automated, bool $interactive): bool
    {
        return $automated || $interactive;
    }

    private function shouldSetupDemo(bool $automated, bool $interactive): bool
    {
        if (!$this->shouldPrompt($automated, $interactive)) {
            return true;
        }
        return $this->promptSetupDemo();
    }

    private function shouldPrompt(bool $automated, bool $interactive): bool
    {
        return $interactive && !$automated;
    }

    private function promptSetupDemo(): bool
    {
        fwrite(STDOUT, "Keine .local/env-*.ini gefunden. Demo-Umgebung anlegen? [y/N] ");
        $answer = trim((string) fgets(STDIN));
        return strtolower($answer) === 'y';
    }

    private function copyDemoEnv(string $fixturePath, string $localDir, string $profilePath): void
    {
        $content = file_get_contents($fixturePath);
        if ($content === false) {
            throw new \RuntimeException("Failed to read demo fixture: {$fixturePath}");
        }

        if (!is_dir($localDir)) {
            mkdir($localDir, 0775, true);
        }

        file_put_contents($profilePath, $content);
        fwrite(STDOUT, $profilePath . " erstellt.\n");
    }
}
