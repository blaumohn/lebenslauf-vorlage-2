<?php

declare(strict_types=1);

use App\Http\Storage\FileStorage;
use App\Http\Storage\StorageException;
use PHPUnit\Framework\TestCase;

final class StorageExceptionTest extends TestCase
{
    private string $readOnlyTempDir;

    protected function setUp(): void
    {
        $this->readOnlyTempDir = sys_get_temp_dir() . '/storage-test-' . bin2hex(random_bytes(6));
        mkdir($this->readOnlyTempDir, 0555, true);
    }

    protected function tearDown(): void
    {
        @chmod($this->readOnlyTempDir, 0775);
        $this->removeDir($this->readOnlyTempDir);
    }

    public function testWriteTextThrowsOnPermissionError(): void
    {
        $storage = new FileStorage();
        $path = $this->readOnlyTempDir . '/file.txt';

        if (is_writable($this->readOnlyTempDir)) {
            $this->markTestSkipped('Temp dir is writable; cannot simulate permission error.');
        }

        $warning = $this->expectExceptionAndCaptureWarning(
            fn () => $storage->writeText($path, 'data')
        );
        $this->assertNotSame('', $warning);
    }

    private function expectExceptionAndCaptureWarning(callable $action): string
    {
        $warning = '';
        set_error_handler(static function (int $level, string $message) use (&$warning): bool {
            if (($level & E_WARNING) === 0) {
                return false;
            }
            $warning = $message;
            return true;
        });
        try {
            $this->expectException(StorageException::class);
            $action();
        } finally {
            restore_error_handler();
        }
        return $warning;
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
