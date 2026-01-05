<?php

namespace App\Cv;

final class RedactionService
{
    public function redact(array $data): array
    {
        if (!isset($data['kopfdaten']) || !is_array($data['kopfdaten'])) {
            return $data;
        }

        $kopfdaten = $data['kopfdaten'];
        $name = $kopfdaten['name'] ?? '';
        if (is_array($name)) {
            $kurz = $name['kurz'] ?? $name['voll'] ?? '';
            $voll = $name['voll'] ?? $kurz;
            $kopfdaten['name'] = [
                'voll' => $voll,
                'kurz' => $kurz,
            ];
        } else {
            $kopfdaten['name'] = [
                'voll' => (string) $name,
                'kurz' => (string) $name,
            ];
        }

        $kopfdaten['ort'] = 'Kontaktformular';
        $kopfdaten['email'] = 'Kontaktformular';
        $kopfdaten['telefon'] = 'Kontaktformular';

        $data['kopfdaten'] = $kopfdaten;
        return $data;
    }
}
