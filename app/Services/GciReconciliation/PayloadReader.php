<?php
namespace App\Services\GciReconciliation;

use Illuminate\Validation\ValidationException;

class PayloadReader
{
    public const HAS = 'HAS_PRECURATION_ID';
    public const NO = 'NO_PRECURATION_ID';

    public function read(string $json, string $source): array
    {
        if (!in_array($source, [self::HAS, self::NO], true)) {
            $this->invalid('Unknown source type.');
        }
        try {
            $object = json_decode($json, false, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            $this->invalid('Malformed JSON: '.$e->getMessage());
        }
        if (!is_object($object) || !isset($object->Items) || !is_array($object->Items)) {
            $this->invalid('The JSON must contain an Items array.');
        }
        foreach ($object->Items as $index => $row) {
            $prefix = 'Record '.($index + 1).': ';
            foreach (['PK', 'gene', 'disease', 'affiliation', 'modeInheritance'] as $field) {
                if (!is_object($row) || !isset($row->$field) || !is_scalar($row->$field) || trim((string) $row->$field) === '') {
                    $this->invalid($prefix.'missing or invalid '.$field.'.');
                }
            }
            foreach (['PK', 'gene', 'disease', 'modeInheritance'] as $field) {
                if (!is_string($row->$field)) $this->invalid($prefix.$field.' must be a string.');
            }
            if (!\Illuminate\Support\Str::isUuid($row->PK)) {
                $this->invalid($prefix.'PK must be a UUID.');
            }
            if ((!is_string($row->affiliation) && !is_int($row->affiliation)) || !ctype_digit((string) $row->affiliation) || (int) $row->affiliation <= 0) {
                $this->invalid($prefix.'affiliation must be a positive numeric identifier.');
            }
            if ($source === self::HAS && (!isset($row->precurationId) || (!is_string($row->precurationId) && !is_int($row->precurationId))
                || !ctype_digit((string) $row->precurationId) || (int) $row->precurationId <= 0 || (float) $row->precurationId > 4294967295)) {
                $this->invalid($prefix.'the selected file type requires a valid precurationId.');
            }
            if ($source === self::NO && isset($row->precurationId) && $row->precurationId !== '') {
                $this->invalid($prefix.'the selected file type must not contain precurationId values.');
            }
        }
        return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    }

    private function invalid(string $message): never
    {
        throw ValidationException::withMessages(['file' => $message]);
    }
}
