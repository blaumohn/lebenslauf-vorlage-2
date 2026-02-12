<?php

namespace App\Http\Security;

use App\Http\Storage\FileStorage;

final class IpSaltStateReader
{
    private const STATE_FILE = 'ip_salt.state.json';

    private FileStorage $storage;
    private string $stateDir;

    public function __construct(FileStorage $storage, string $stateDir)
    {
        $this->storage = $storage;
        $this->stateDir = rtrim($stateDir, DIRECTORY_SEPARATOR);
    }

    public function readState(): IpSaltState
    {
        $raw = $this->storage->readText($this->statePath());
        if ($raw === null) {
            return $this->emptyState();
        }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return $this->emptyState();
        }
        $salt = $this->readStringField($decoded, 'salt');
        $fingerprint = $this->readStringField($decoded, 'fingerprint');
        $status = $this->readStatus($decoded);
        $generation = $this->readGeneration($decoded);
        return new IpSaltState($salt, $fingerprint, $status, $generation);
    }

    private function readStringField(array $payload, string $key): ?string
    {
        $value = $payload[$key] ?? null;
        if (!is_string($value)) {
            return null;
        }
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        return $value;
    }

    private function readStatus(array $payload): ?string
    {
        $status = $this->readStringField($payload, 'status');
        if ($status === IpSaltState::STATUS_IN_PROGRESS) {
            return $status;
        }
        if ($status === IpSaltState::STATUS_READY) {
            return $status;
        }
        return null;
    }

    private function readGeneration(array $payload): int
    {
        $value = $payload['generation'] ?? null;
        if (is_int($value) && $value > 0) {
            return $value;
        }
        if (!is_string($value) || !ctype_digit($value)) {
            return 0;
        }
        $parsed = (int) $value;
        if ($parsed > 0) {
            return $parsed;
        }
        return 0;
    }

    private function emptyState(): IpSaltState
    {
        return new IpSaltState(null, null, null, 0);
    }

    private function statePath(): string
    {
        return $this->stateDir . DIRECTORY_SEPARATOR . self::STATE_FILE;
    }
}
