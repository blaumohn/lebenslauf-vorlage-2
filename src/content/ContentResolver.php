<?php

namespace App\Content;

final class ContentResolver
{
    private string $rootPath;
    private ContentPaths $paths;

    public function __construct(string $rootPath)
    {
        $this->rootPath = rtrim($rootPath, DIRECTORY_SEPARATOR);
        $this->paths = new ContentPaths($this->rootPath);
    }

    public function ensureLocalContent(bool $interactive): void
    {
        $targetPath = $this->paths->localContentFile();
        if (is_file($targetPath)) {
            return;
        }
        if (!$this->shouldAttemptSetup($interactive)) {
            return;
        }
        if (!$this->shouldSetupDemo($interactive)) {
            return;
        }
        $this->copyDemo($this->paths->demoFixtureFile(), $targetPath);
    }

    private function shouldAttemptSetup(bool $interactive): bool
    {
        return $this->isAutomated() || $interactive;
    }

    private function shouldSetupDemo(bool $interactive): bool
    {
        if (!$this->shouldPrompt($interactive)) {
            return true;
        }
        return $this->promptSetupDemo();
    }

    private function shouldPrompt(bool $interactive): bool
    {
        return $interactive && !$this->isAutomated();
    }

    private function isAutomated(): bool
    {
        return getenv('AUTO_ENV_SETUP') === '1';
    }

    private function promptSetupDemo(): bool
    {
        fwrite(STDOUT, "Keine .local/content.ini gefunden. Demo-Inhalt anlegen? [y/N] ");
        $answer = trim((string) fgets(STDIN));
        return strtolower($answer) === 'y';
    }

    private function copyDemo(string $fixturePath, string $targetPath): void
    {
        $content = file_get_contents($fixturePath);
        if ($content === false) {
            throw new \RuntimeException("Demo-Content nicht lesbar: {$fixturePath}");
        }
        $dir = dirname($targetPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        file_put_contents($targetPath, $content);
        fwrite(STDOUT, $targetPath . " erstellt.\n");
    }
}
