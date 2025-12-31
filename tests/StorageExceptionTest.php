<?php

declare(strict_types=1);

use App\Storage\FileStorage;
use App\Storage\StorageException;
use PHPUnit\Framework\TestCase;

final class StorageExceptionTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/storage-test-' . bin2hex(random_bytes(6));
        mkdir($this->tempDir, 0555, true);
    }

    protected function tearDown(): void
    {
        @chmod($this->tempDir, 0775);
        $this->removeDir($this->tempDir);
    }

    public function testWriteTextThrowsOnPermissionError(): void
    {
        $storage = new FileStorage();
        $path = $this->tempDir . '/file.txt';

        if (is_writable($this->tempDir)) {
            $this->markTestSkipped('Temp dir is writable; cannot simulate permission error.');
        }

        $this->expectException(StorageException::class);
        $storage->writeText($path, 'data');
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
