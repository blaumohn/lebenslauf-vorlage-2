<?php

namespace App\Cv;

final class CvDataNormalizer
{
    private string $lang;

    public function __construct(string $lang = '')
    {
        $this->lang = strtolower(trim($lang));
    }

    public function normalize(array $data): array
    {
        return $this->normalizeValue($data);
    }

    private function normalizeValue(mixed $value): mixed
    {
        if (is_array($value)) {
            if ($this->isNameObject($value)) {
                return [
                    'voll' => $this->normalizeValue($value['voll'] ?? ''),
                    'verkurzte' => $this->normalizeValue($value['verkurzte'] ?? ''),
                ];
            }

            if ($this->isIntlString($value)) {
                return $this->pickLang($value);
            }

            $normalized = [];
            foreach ($value as $key => $item) {
                $normalized[$key] = $this->normalizeValue($item);
            }
            return $normalized;
        }

        return $value;
    }

    private function isNameObject(array $value): bool
    {
        return array_key_exists('voll', $value) || array_key_exists('verkurzte', $value);
    }

    private function isIntlString(array $value): bool
    {
        return $this->isAssoc($value) && $this->looksLikeLanguageMap($value);
    }

    private function pickLang(array $value): string
    {
        if ($this->lang !== '' && isset($value[$this->lang])) {
            return (string) $value[$this->lang];
        }

        if (isset($value['de'])) {
            return (string) $value['de'];
        }

        $first = reset($value);
        return is_string($first) ? $first : '';
    }

    private function isAssoc(array $value): bool
    {
        return array_keys($value) !== range(0, count($value) - 1);
    }

    private function looksLikeLanguageMap(array $value): bool
    {
        foreach ($value as $key => $item) {
            if (!is_string($key) || !is_string($item)) {
                return false;
            }
            if (!preg_match('/^[a-z]{2,5}(-[a-z]{2})?$/i', $key)) {
                return false;
            }
        }
        return count($value) > 0;
    }
}
