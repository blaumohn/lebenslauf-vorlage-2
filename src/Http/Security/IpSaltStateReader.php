<?php

namespace App\Http\Security;

use App\Http\Storage\FileStorage;

final class IpSaltStateReader
{
    private FileStorage $storage;
    private string $stateDir;

    public function __construct(FileStorage $storage, string $stateDir)
    {
        $this->storage = $storage;
        $this->stateDir = rtrim($stateDir, DIRECTORY_SEPARATOR);
    }

    public function readState(): IpSaltState
    {
        $salt = $this->readTrimmed($this->saltPath());
        $fingerprint = $this->readTrimmed($this->fingerprintPath());
        return new IpSaltState($salt, $fingerprint);
    }

    private function readTrimmed(string $path): ?string
    {
        $content = $this->storage->readText($path);
        if ($content === null) {
            return null;
        }
        $value = trim($content);
        if ($value === '') {
            return null;
        }
        return $value;
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
