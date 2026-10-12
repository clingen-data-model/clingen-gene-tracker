<?php

namespace App\Notifications;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

trait HasValidUniqueNotifications
{
    public static function getUnique(Collection $collection): Collection
    {
        return $collection->unique(fn ($item) => static::digestUniqueKey($item))->values();
    }

    public static function filterInvalid(Collection $collection): Collection
    {
        return $collection;
    }

    public static function getValidUnique(Collection $collection): Collection
    {
        return static::getUnique(static::filterInvalid($collection));
    }

    protected static function digestKeyForFields($item, array $fields): string
    {
        $values = [];
        foreach ($fields as $field) {
            if (!Arr::has($item->data, $field)) {
                // Historical snapshots may lack fields. Compare their full content rather than guessing missing values from today's models.
                return static::digestContentKey(Arr::except($item->data, ['template', 'digest_event_id']));
            }
            $values[$field] = data_get($item->data, $field);
        }

        return static::digestContentKey($values);
    }

    protected static function digestContentKey(array $data): string
    {
        $normalize = function ($value) use (&$normalize) {
            if (!is_array($value)) {
                return $value;
            }
            if (!array_is_list($value)) {
                ksort($value);
            }
            return array_map($normalize, $value);
        };

        return hash('sha256', json_encode($normalize($data), JSON_THROW_ON_ERROR));
    }
}
