<?php

namespace App\Curations;

use Illuminate\Database\QueryException;

/**
 * Recognising a unique-constraint violation.
 *
 * The history tables lean on unique indexes to enforce idempotency, so writers
 * treat a duplicate key as "already applied" and carry on. That is only safe if
 * the check is narrow: catching every QueryException would let a genuine database
 * error pass as a routine skip, which matters most in the commands that rewrite
 * historical data.
 */
class DuplicateKey
{
    public static function matches(QueryException $e): bool
    {
        return $e->getCode() === '23000';
    }
}
