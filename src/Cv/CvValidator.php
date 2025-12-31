<?php

namespace App\Cv;

use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\SchemaLoader;
use Opis\JsonSchema\Validator;

final class CvValidator
{
    private string $schemaPath;

    public function __construct(string $schemaPath)
    {
        $this->schemaPath = $schemaPath;
    }

    public function validate(mixed $data): array
    {
        [$loader, $schema, $schemaError] = $this->loadSchema();
        if ($schemaError !== null) {
            return [$schemaError];
        }

        if (is_array($data)) {
            $data = $this->normalizeArrayData($data);
            if ($data === null) {
                return ['Daten sind ungueltig.'];
            }
        }

        $validator = new Validator($loader);
        $result = $validator->schemaValidation($data, $schema);
        if ($result === null) {
            return [];
        }

        $formatter = new ErrorFormatter();
        $errors = $formatter->format($result);

        return $this->flattenErrors($errors);
    }

    private function flattenErrors(array $errors, string $prefix = ''): array
    {
        $messages = [];
        foreach ($errors as $key => $value) {
            $path = $prefix === '' ? $key : $prefix . '.' . $key;
            if (!is_array($value)) {
                $messages[] = $path . ': ' . (string) $value;
                continue;
            }

            $messages = array_merge($messages, $this->flattenErrors($value, $path));
        }

        return $messages;
    }

    private function loadSchema(): array
    {
        $schemaJson = file_get_contents($this->schemaPath);
        if ($schemaJson === false) {
            return [null, null, 'Schema konnte nicht geladen werden.'];
        }

        $decoded = json_decode($schemaJson);
        if ($decoded === null) {
            return [null, null, 'Schema ist ungueltig.'];
        }

        $loader = new SchemaLoader();
        try {
            $schema = is_bool($decoded)
                ? $loader->loadBooleanSchema($decoded)
                : $loader->loadObjectSchema($decoded);
        } catch (\Throwable $exception) {
            return [null, null, 'Schema ist ungueltig.'];
        }

        return [$loader, $schema, null];
    }

    private function normalizeArrayData(array $data): mixed
    {
        $normalized = json_decode(json_encode($data));
        if ($normalized === null && json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        return $normalized;
    }
}
