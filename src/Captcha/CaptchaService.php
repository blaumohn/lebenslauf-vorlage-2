<?php

namespace App\Captcha;

use App\Storage\FileStorage;

final class CaptchaService
{
    private FileStorage $storage;
    private string $dir;
    private int $ttlSeconds;

    public function __construct(FileStorage $storage, string $dir, int $ttlSeconds)
    {
        $this->storage = $storage;
        $this->dir = rtrim($dir, DIRECTORY_SEPARATOR);
        $this->ttlSeconds = $ttlSeconds;
        $this->storage->ensureDir($this->dir);
    }

    public function createChallenge(string $ipHash): array
    {
        $id = bin2hex(random_bytes(16));
        $solution = $this->generateSolution();
        $now = time();
        $data = [
            'captcha_id' => $id,
            'solution_text' => $solution,
            'ip_hash' => $ipHash,
            'created_at' => $now,
            'expires_at' => $now + $this->ttlSeconds,
            'used_at' => null,
            'fail_count' => 0,
        ];

        $this->storage->writeJson($this->pathFor($id), $data);
        return $data;
    }

    public function getChallenge(string $id): ?array
    {
        $data = $this->storage->readJson($this->pathFor($id));
        if (!$data) {
            return null;
        }

        if ($this->isExpired($data) || $this->isUsed($data)) {
            return null;
        }

        return $data;
    }


    public function verify(string $id, string $answer, string $ipHash): bool
    {
        $path = $this->pathFor($id);
        $data = $this->storage->readJson($path);
        if (!$data) {
            return false;
        }

        if ($this->isExpired($data) || $this->isUsed($data)) {
            return false;
        }

        if (!hash_equals($data['ip_hash'] ?? '', $ipHash)) {
            return false;
        }

        $expected = strtoupper((string) ($data['solution_text'] ?? ''));
        $actual = strtoupper(trim($answer));

        if (!hash_equals($expected, $actual)) {
            $data['fail_count'] = (int) ($data['fail_count'] ?? 0) + 1;
            $this->storage->writeJson($path, $data);
            return false;
        }

        $data['used_at'] = time();
        $this->storage->writeJson($path, $data);
        return true;
    }

    public function cleanupExpired(): int
    {
        $files = glob($this->dir . DIRECTORY_SEPARATOR . '*.json') ?: [];
        $count = 0;
        foreach ($files as $file) {
            $data = $this->storage->readJson($file);
            if (!$data) {
                continue;
            }
            if ($this->isExpired($data) || $this->isUsed($data)) {
                $this->storage->delete($file);
                $count++;
            }
        }

        return $count;
    }

    public function renderPng(string $text): string
    {
        if (!function_exists('imagecreatetruecolor')) {
            return '';
        }

        $width = 160;
        $height = 50;
        $image = imagecreatetruecolor($width, $height);

        $bg = imagecolorallocate($image, 245, 245, 245);
        $fg = imagecolorallocate($image, 30, 30, 30);
        $noise = imagecolorallocate($image, 180, 180, 180);

        imagefilledrectangle($image, 0, 0, $width, $height, $bg);

        for ($i = 0; $i < 50; $i++) {
            imageline(
                $image,
                rand(0, $width),
                rand(0, $height),
                rand(0, $width),
                rand(0, $height),
                $noise
            );
        }

        imagestring($image, 5, 20, 15, $text, $fg);

        ob_start();
        imagepng($image);
        $png = ob_get_clean();
        return $png === false ? '' : $png;
    }

    private function pathFor(string $id): string
    {
        return $this->dir . DIRECTORY_SEPARATOR . $id . '.json';
    }

    private function generateSolution(): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $solution = '';
        for ($i = 0; $i < 6; $i++) {
            $solution .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $solution;
    }

    private function isExpired(array $data): bool
    {
        return (int) ($data['expires_at'] ?? 0) < time();
    }

    private function isUsed(array $data): bool
    {
        return !empty($data['used_at']);
    }
}
