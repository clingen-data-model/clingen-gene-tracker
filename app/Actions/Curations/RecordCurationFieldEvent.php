<?php

namespace App\Actions\Curations;

use App\Curation;
use App\Curations\CurationField;
use App\Curations\DuplicateKey;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * The single write path for curation field history.
 *
 * Idempotency has two layers, and they answer different questions:
 *
 *  1. Hard layer, enforced by a unique index on (curation_id, source_event_key):
 *     the same source event can never be recorded twice. This is what makes
 *     replaying a Kafka message a no-op, and it holds under concurrency because
 *     the database, not a PHP check, enforces it.
 *
 *  2. Soft layer, in PHP: a *different* source event asserting the value the
 *     timeline already holds at that point is not recorded. This only affects
 *     noise, never correctness.
 *
 * An event dated earlier than the newest known event is still recorded; the
 * projector decides what the current value is, so history stays complete without
 * an old event clobbering current state.
 */
class RecordCurationFieldEvent
{
    use AsAction;

    public function handle(
        Curation $curation,
        CurationField $field,
        int $valueId,
        $effectiveAt,
        string $source,
        string $sourceEventKey,
        array $attributes = []
    ): bool {
        $effectiveAt = $this->normalizeDate($field, $effectiveAt);

        if ($this->alreadyRecorded($curation, $field, $sourceEventKey)) {
            return false;
        }

        if ($field->collapsesConsecutiveDuplicates()
            && $curation->valueAt($field, $effectiveAt) === $valueId
        ) {
            return false;
        }

        $inserted = $this->insert($curation, $field, $valueId, $effectiveAt, $source, $sourceEventKey, $attributes);

        if (!$inserted) {
            return false;
        }

        ProjectCurationField::run($curation, $field);

        return true;
    }

    /**
     * Whatever precision the caller supplied is kept. A caller that only knows the
     * date passes a date, and midnight then means "time unknown" rather than
     * "midnight".
     */
    private function normalizeDate(CurationField $field, $effectiveAt): Carbon
    {
        return $effectiveAt ? Carbon::parse($effectiveAt) : Carbon::now();
    }

    private function alreadyRecorded(Curation $curation, CurationField $field, string $sourceEventKey): bool
    {
        return DB::table($field->historyTable())
            ->where('curation_id', $curation->getKey())
            ->where('source_event_key', $sourceEventKey)
            ->exists();
    }

    private function insert(
        Curation $curation,
        CurationField $field,
        int $valueId,
        Carbon $effectiveAt,
        string $source,
        string $sourceEventKey,
        array $attributes
    ): bool {
        $now = Carbon::now();

        try {
            DB::table($field->historyTable())->insert(array_merge($attributes, [
                'curation_id' => $curation->getKey(),
                $field->valueColumn() => $valueId,
                $field->dateColumn() => $effectiveAt->format('Y-m-d H:i:s'),
                'source' => $source,
                'source_event_key' => $sourceEventKey,
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        } catch (QueryException $e) {
            // A concurrent worker won the race, or a different source event already
            // asserts this value at this instant. Either way it is already applied.
            if (DuplicateKey::matches($e)) {
                return false;
            }

            throw $e;
        }

        return true;
    }
}
