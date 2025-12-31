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
        $schemaJson = file_get_contents($this->schemaPath);
        if ($schemaJson === false) {
            return ['Schema konnte nicht geladen werden.'];
        }

        $decoded = json_decode($schemaJson);
        if ($decoded === null) {
            return ['Schema ist ungueltig.'];
        }

        try {
            $loader = new SchemaLoader();
            if (is_bool($decoded)) {
                $schema = $loader->loadBooleanSchema($decoded);
            } else {
                $schema = $loader->loadObjectSchema($decoded);
            }
        } catch (\Throwable $exception) {
            return ['Schema ist ungueltig.'];
        }

        if (is_array($data)) {
            $data = json_decode(json_encode($data));
            if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
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
            if (is_array($value)) {
                $messages = array_merge($messages, $this->flattenErrors($value, $path));
            } else {
                $messages[] = $path . ': ' . (string) $value;
            }
        }

        return $messages;
    }
}
