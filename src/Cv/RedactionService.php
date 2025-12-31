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
            $verkurzte = $name['verkurzte'] ?? $name['voll'] ?? '';
            $voll = $name['voll'] ?? $verkurzte;
            $kopfdaten['name'] = [
                'voll' => $voll,
                'verkurzte' => $verkurzte,
            ];
        } else {
            $kopfdaten['name'] = [
                'voll' => (string) $name,
                'verkurzte' => (string) $name,
            ];
        }

        $kopfdaten['ort'] = 'Kontaktformular';
        $kopfdaten['email'] = 'Kontaktformular';
        $kopfdaten['telefon'] = 'Kontaktformular';

        $data['kopfdaten'] = $kopfdaten;
        return $data;
    }
}
