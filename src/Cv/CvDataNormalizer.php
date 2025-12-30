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

    private function isIntlString(array $value): bool
    {
        foreach ($value as $item) {
            if (is_string($item)) {
                return true;
            }
        }
        return false;
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
}
