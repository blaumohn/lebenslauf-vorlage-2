<?php

namespace App\Http\Security;

final class IpSaltResetExecutor
{
    private const STATE_FILE = 'ip_salt.state.json';

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

    public function markInProgress(IpSaltState $state): IpSaltState
    {
        $next = $state->withMarker(IpSaltState::STATUS_IN_PROGRESS, $state->nextGeneration());
        $this->writeState($next);
        return $next;
    }

    public function rotateAndClear(IpSaltState $state): IpSaltState
    {
        $this->assertInProgressState($state);
        $salt = $this->generateSalt();
        $fingerprint = $this->validator->fingerprintFor($salt);
        $this->clearIpState();
        $ready = new IpSaltState(
            $salt,
            $fingerprint,
            IpSaltState::STATUS_READY,
            $state->generation()
        );
        $this->writeState($ready);
        return $ready;
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

    private function writeState(IpSaltState $state): void
    {
        $payload = $this->buildStatePayload($state);
        $encoded = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if (!is_string($encoded)) {
            throw new \RuntimeException('IP-Salt-State konnte nicht serialisiert werden.');
        }
        $this->writer->writeText($this->statePath(), $encoded . "\n");
    }

    private function buildStatePayload(IpSaltState $state): array
    {
        return [
            'salt' => $state->salt(),
            'fingerprint' => $state->fingerprint(),
            'status' => $state->status(),
            'generation' => $state->generation(),
            'updated_at' => gmdate('c'),
        ];
    }

    private function assertInProgressState(IpSaltState $state): void
    {
        if ($state->status() !== IpSaltState::STATUS_IN_PROGRESS) {
            throw new \RuntimeException('IP-Salt-State ist nicht IN_PROGRESS.');
        }
        if ($state->generation() > 0) {
            return;
        }
        throw new \RuntimeException('IP-Salt-State hat keine gueltige Generation.');
    }

    private function statePath(): string
    {
        return $this->stateDir . DIRECTORY_SEPARATOR . self::STATE_FILE;
    }
}
