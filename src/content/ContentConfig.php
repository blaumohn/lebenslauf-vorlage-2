<?php

namespace App\Content;

use Symfony\Component\Filesystem\Path;

final class ContentConfig
{
    private array $data;

    public function __construct(string $rootPath)
    {
        $this->data = $this->loadData($rootPath);
    }

    public function siteName(): string
    {
        return $this->getString('site', 'name', 'Lebenslauf');
    }

    public function defaultLang(): string
    {
        return $this->getString('site', 'lang', 'de');
    }

    public function langs(): array
    {
        $raw = $this->getString('site', 'langs', '');
        return $this->parseLangs($raw, $this->defaultLang());
    }

    public function defaultProfile(): string
    {
        return $this->getString('cv', 'default_profile', 'default');
    }

    public function cvProfile(): string
    {
        return $this->getString('cv', 'profile', $this->defaultProfile());
    }

    public function contactTo(): string
    {
        return $this->getString('contact', 'to', '');
    }

    public function contactFrom(): string
    {
        return $this->getString('contact', 'from', '');
    }

    public function contactSubject(): string
    {
        return $this->getString('contact', 'subject', 'Kontaktformular');
    }

    private function getString(string $section, string $key, string $default): string
    {
        $value = $this->data[$section][$key] ?? null;
        if ($value === null) {
            return $default;
        }
        return trim((string) $value);
    }

    private function loadData(string $rootPath): array
    {
        $path = Path::join($rootPath, '.local', 'content.ini');
        if (!is_file($path)) {
            throw new \RuntimeException("content.ini fehlt: {$path}");
        }
        $data = parse_ini_file($path, true, INI_SCANNER_RAW);
        if (!is_array($data)) {
            throw new \RuntimeException("content.ini ungültig: {$path}");
        }
        return $data;
    }

    private function parseLangs(string $raw, string $fallback): array
    {
        $raw = trim($raw);
        if ($raw === '') {
            $fallback = trim($fallback);
            return $fallback === '' ? [] : [strtolower($fallback)];
        }
        $parts = preg_split('/\s*,\s*/', $raw);
        if ($parts === false) {
            return [];
        }
        return $this->normalizeLangs($parts);
    }

    private function normalizeLangs(array $parts): array
    {
        $langs = [];
        foreach ($parts as $part) {
            $value = strtolower(trim((string) $part));
            if ($value !== '') {
                $langs[] = $value;
            }
        }
        return array_values(array_unique($langs));
    }
}
