<?php

declare(strict_types=1);

use App\Captcha\CaptchaService;
use App\Config;
use App\Http\AppBuilder;
use App\Storage\FileStorage;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ServerRequestFactory;

final class AppTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/php-mvp-app-' . bin2hex(random_bytes(6));
        mkdir($this->root, 0775, true);
        $this->copyDir($this->projectRoot() . '/templates', $this->root . '/templates');
        $this->writeEnv($this->root . '/.env');
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->root);
    }

    public function testContactFormRenders(): void
    {
        $config = new Config($this->root);
        $app = AppBuilder::build($config);

        $request = (new ServerRequestFactory())->createServerRequest('GET', '/contact');
        $response = $app->handle($request);

        $this->assertSame(200, $response->getStatusCode());
        $body = (string) $response->getBody();
        $this->assertStringContainsString('/captcha.png?id=', $body);
    }

    public function testCaptchaPngReturnsBinary(): void
    {
        $config = new Config($this->root);
        $app = AppBuilder::build($config);

        $storage = new FileStorage();
        $service = new CaptchaService($storage, $this->root . '/var/tmp/captcha', 600);
        $challenge = $service->createChallenge('iphash');

        $request = (new ServerRequestFactory())
            ->createServerRequest('GET', '/captcha.png?id=' . $challenge['captcha_id']);
        $response = $app->handle($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('image/png', $response->getHeaderLine('Content-Type'));

        $body = (string) $response->getBody();
        $this->assertNotEmpty($body);
        $this->assertSame("\x89PNG\r\n\x1a\n", substr($body, 0, 8));
    }

    private function projectRoot(): string
    {
        return dirname(__DIR__, 2);
    }

    private function writeEnv(string $path): void
    {
        $content = [
            'APP_ENV=dev',
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
