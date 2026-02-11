<?php

namespace App\Http\Security;

final class IpSaltResetExecutor
{
    private RuntimeAtomicWriter $writer;
    private IpSaltStateValidator $validator;
    private string $stateDir;
    private string $captchaDir;
    private string $rateLimitDir;

    public function __construct(
        RuntimeAtomicWriter $writer,
        IpSaltStateValidator $validator,
        string $stateDir,
        string $captchaDir,
        string $rateLimitDir
    ) {
        $this->writer = $writer;
        $this->validator = $validator;
        $this->stateDir = rtrim($stateDir, DIRECTORY_SEPARATOR);
        $this->captchaDir = rtrim($captchaDir, DIRECTORY_SEPARATOR);
        $this->rateLimitDir = rtrim($rateLimitDir, DIRECTORY_SEPARATOR);
    }

    public function rotateAndClear(): string
    {
        $salt = $this->generateSalt();
        $fingerprint = $this->validator->fingerprintFor($salt);
        $this->writer->writeText($this->saltPath(), $salt . "\n");
        $this->writer->writeText($this->fingerprintPath(), $fingerprint . "\n");
        $this->clearIpState();
        return $salt;
    }

    private function generateSalt(): string
    {
        return bin2hex(random_bytes(16));
    }

    private function clearIpState(): void
    {
        $this->clearDirFiles($this->captchaDir);
        $this->clearDirFiles($this->rateLimitDir);
    }

    private function clearDirFiles(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $items = scandir($dir);
        if (!is_array($items)) {
            throw new \RuntimeException("Verzeichnis nicht lesbar: {$dir}");
        }
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (!is_file($path)) {
                continue;
            }
            if (@unlink($path)) {
                continue;
            }
            throw new \RuntimeException("Datei konnte nicht geloescht werden: {$path}");
        }
    }

    private function saltPath(): string
    {
        return $this->stateDir . DIRECTORY_SEPARATOR . 'ip_salt.txt';
    }

    private function fingerprintPath(): string
    {
        return $this->stateDir . DIRECTORY_SEPARATOR . 'ip_salt.fingerprint';
    }
}
