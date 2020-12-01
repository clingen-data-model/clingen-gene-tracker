<?php

namespace App\Traits;

use Ramsey\Uuid\Uuid;

/**
 * Provides basic functionality for working with models that have UUIDs.
 */
trait HasUuid
{
    protected static function bootHasUuid()
    {
        static::creating(function ($model) {
            if (!$model->uuid) {
                $model->uuid = static::createUuid();
            }
        });
    }

    public static function findByUuid($uuid)
    {
        dd($uuid);

        return static::where('uuid', $uuid)->first();
    }

    private static function createUuid()
    {
        return Uuid::uuid4()->toString();
    }
}
