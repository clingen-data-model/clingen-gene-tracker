<?php

namespace App\Notifications;

use App\DataExchange\Notifications\StreamErrorNotification;
use App\Notifications\Curations\OmimOutdatedPhenotypesNotification;

class DigestEventIdentity
{
    public static function key($notification): string
    {
        $data = $notification->data;
        $prefix = $notification->type.'|';
        if (!empty($data['digest_event_id'])) {
            return $prefix.'event:'.$data['digest_event_id'];
        }

        // These historical batches retain source identity. Other historical
        // records cannot distinguish repeated occurrences safely.
        if ($notification->type === StreamErrorNotification::class) {
            $ids = collect($data['stream_errors'] ?? [])->pluck('id');
            if ($ids->isNotEmpty() && $ids->every(fn ($id) => $id !== null && $id !== '')) {
                return $prefix.'event:stream-errors:'.self::hash($ids->sort()->values()->all());
            }
        }
        if ($notification->type === OmimOutdatedPhenotypesNotification::class && !empty($data['digest_key'])) {
            // Include content: older digest keys omit the phenotype details.
            return $prefix.'event:omim-batch:'.self::hash($data);
        }

        return $prefix.'record:'.$notification->id;
    }

    public static function hash(array $data): string
    {
        // Respect model/date JSON serialization, excluding object internals.
        $data = json_decode(json_encode($data, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
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
