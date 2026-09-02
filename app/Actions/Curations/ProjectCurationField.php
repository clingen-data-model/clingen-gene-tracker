<?php

namespace App\Actions\Curations;

use App\Curation;
use App\Curations\CurationField;
use App\Events\Curation\CurrentOwnerChanged;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

/**
 * Recomputes everything derived from a field's history rows: the interval
 * end_dates, and the denormalized "current value" column on `curations`.
 *
 * The history rows are the source of truth. This action never invents a row; it
 * only makes the derived data agree with them, which is why it can be re-run at
 * any time to repair drift.
 */
class ProjectCurationField
{
    use AsAction;

    public function handle(Curation $curation, CurationField $field): void
    {
        DB::transaction(function () use ($curation, $field) {
            // Serialize concurrent queue workers projecting the same curation.
            $locked = Curation::withTrashed()->whereKey($curation->getKey())->lockForUpdate()->first();

            if (!$locked) {
                return;
            }

            $rows = $this->timeline($locked, $field);

            if ($field->isInterval()) {
                $this->deriveIntervals($field, $rows);
            }

            $this->updateCurrentValue($locked, $field, $rows->last());

            $curation->setRawAttributes($locked->getAttributes(), true);
        });
    }

    /**
     * Every history row for the field, oldest first, with same-date rows ordered so
     * that the last one is the one that stands. See CurationField::tiebreakColumn().
     */
    private function timeline(Curation $curation, CurationField $field)
    {
        return DB::table($field->historyTable())
            ->where('curation_id', $curation->getKey())
            ->orderBy($field->dateColumn())
            ->orderBy($field->tiebreakColumn())
            ->orderBy('id')
            ->get();
    }

    /**
     * An ownership row ends when the next one begins. Deriving end_date rather than
     * maintaining it is what makes an out-of-order or corrective transfer safe, and
     * what allows A -> B -> A without the rows interfering with each other.
     */
    private function deriveIntervals(CurationField $field, $rows): void
    {
        $dateColumn = $field->dateColumn();

        foreach ($rows as $index => $row) {
            $next = $rows->get($index + 1);
            $endDate = $next ? (string) $next->{$dateColumn} : null;

            if ($row->end_date === $endDate) {
                continue;
            }

            DB::table($field->historyTable())
                ->where('id', $row->id)
                ->update(['end_date' => $endDate]);
        }
    }

    /**
     * The current value is the last row by date. An event dated before the newest
     * known event therefore lands in history without displacing the current value.
     *
     * The comparison is what keeps replay silent: Eloquent skips the UPDATE entirely
     * when nothing is dirty, so no `updated` model event fires, so no outgoing stream
     * message is produced and no notification is sent.
     */
    private function updateCurrentValue(Curation $curation, CurationField $field, $winner): void
    {
        $column = $field->currentValueColumn();

        if ($column === null) {
            return;
        }

        $newValue = $winner->{$field->valueColumn()} ?? null;
        $oldValue = $curation->{$column};

        if ((int) $oldValue === (int) $newValue) {
            return;
        }

        if ($newValue === null) {
            return;
        }

        $curation->update([$column => $newValue]);

        if ($field === CurationField::ExpertPanel) {
            CurrentOwnerChanged::dispatch($curation, (int) $oldValue, (int) $newValue);
        }
    }
}
