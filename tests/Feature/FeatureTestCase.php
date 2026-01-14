<?php

declare(strict_types=1);

use App\Env\Env;
use App\Http\AppBuilder;
use PHPUnit\Framework\TestCase;
use Slim\App;

abstract class FeatureTestCase extends TestCase
{
    protected string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/php-mvp-app-' . bin2hex(random_bytes(6));
        putenv('APP_ENV=dev');
        mkdir($this->root, 0775, true);
        $this->copyDir($this->projectRoot() . '/src/resources/templates', $this->root . '/src/resources/templates');
        $this->copyDir($this->projectRoot() . '/labels', $this->root . '/labels');
        $this->ensureDirs([
            $this->root . '/var/tmp/captcha',
            $this->root . '/var/tmp/ratelimit',
            $this->root . '/var/cache/html',
            $this->root . '/var/state/tokens',
        ]);
        $this->writeEnv($this->root . '/.local/env-dev.ini');
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->root);
    }

    protected function app(): App
    {
        $config = new Env($this->root);
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

    private function writeEnv(string $path): void
    {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $content = [
            'APP_ENV=dev',
            'APP_BASE_PATH=',
            'APP_LANG=de',
            'APP_LANGS=de,en',
            'LABELS_PATH=labels/etiketten.json',
            'SITE_NAME=Test',
            'IP_SALT=test-salt',
            'TRUST_PROXY=0',
            'CAPTCHA_TTL_SECONDS=600',
            'CAPTCHA_MAX_GET=5',
            'CONTACT_MAX_POST=3',
            'RATE_LIMIT_WINDOW_SECONDS=600',
            'CONTACT_TO=test@example.com',
            'CONTACT_FROM=web@example.com',
            'MAIL_STDOUT=1',
        ];
        file_put_contents($path, implode("\n", $content) . "\n");
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
