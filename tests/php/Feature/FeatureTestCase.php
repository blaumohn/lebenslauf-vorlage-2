<?php

declare(strict_types=1);

use ConfigPipelineSpec\Config\ConfigCompiler;
use App\Http\AppBuilder;
use App\Http\ConfigCompiled;
use PHPUnit\Framework\TestCase;
use Slim\App;

abstract class FeatureTestCase extends TestCase
{
    protected string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/php-mvp-app-' . bin2hex(random_bytes(6));
        mkdir($this->root, 0775, true);
        $this->copyDir($this->projectRoot() . '/config', $this->root . '/config');
        $this->copyDir($this->projectRoot() . '/src/resources/templates', $this->root . '/src/resources/templates');
        $this->copyFile(
            $this->projectRoot() . '/src/resources/labels.json',
            $this->root . '/src/resources/labels.json'
        );
        $this->ensureDirs([
            $this->root . '/var/tmp/captcha',
            $this->root . '/var/tmp/ratelimit',
            $this->root . '/var/cache/html',
            $this->root . '/var/state/tokens',
            $this->root . '/var/config',
        ]);
        $this->compileConfig();
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->root);
    }

    protected function app(): App
    {
        $config = new ConfigCompiled($this->root);
        return AppBuilder::build($config);
    }

    protected function projectRoot(): string
    {
        return dirname(__DIR__, 2);
    }

    private function ensureDirs(array $dirs): void
    {
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0775, true);
            }
        }
    }

    private function compileConfig(): void
    {
        $compiler = new ConfigCompiler($this->root);
        $context = $compiler->resolveContext([
            'pipeline' => 'dev',
            'phase' => 'runtime',
        ]);
        $compiler->compile($context, false);
    }

    private function copyDir(string $source, string $dest): void
    {
        if (!is_dir($dest)) {
            mkdir($dest, 0775, true);
        }

        $items = scandir($source);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $srcPath = $source . DIRECTORY_SEPARATOR . $item;
            $destPath = $dest . DIRECTORY_SEPARATOR . $item;

            if (is_dir($srcPath)) {
                $this->copyDir($srcPath, $destPath);
            } else {
                copy($srcPath, $destPath);
            }
        }
    }

    private function copyFile(string $source, string $dest): void
    {
        $dir = dirname($dest);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        copy($source, $dest);
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = scandir($dir);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $this->removeDir($path);
            } else {
                unlink($path);
            }
        }

        rmdir($dir);
    }
}
