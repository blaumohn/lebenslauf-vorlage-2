<?php

namespace App\Cv;

final class CvDataNormalizer
{
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
                return $this->firstString($value);
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

    private function firstString(array $value): string
    {
        foreach ($value as $item) {
            if (is_string($item)) {
                return $item;
            }
        }
        return '';
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
