<?php
namespace App\Services\GciReconciliation;

use App\Curation;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class Review
{
    public function save(array $ids, array $decision, int $actor): void
    {
        DB::transaction(function () use ($ids, $decision, $actor) {
            foreach ($ids as $id) {
                $row = Record::lockForUpdate()->findOrFail($id);
                $resolved = $decision['resolved_gt_curation_id'] ?? null;
                if ($resolved && !Curation::find($resolved)) {
                    throw ValidationException::withMessages(['resolved_gt_curation_id' => 'The resolved GT curation is missing or deleted.']);
                }
                if (($decision['review_status'] ?? '') === 'REVIEWED'
                    && in_array($decision['resolution'] ?? '', [Decision::MATCH, Decision::CREATE], true) && !$resolved) {
                    throw ValidationException::withMessages(['resolved_gt_curation_id' => 'Record the existing or manually created GT curation ID. Review does not create curations.']);
                }
                if (($decision['resolution'] ?? '') === Decision::EXPORT && $row->source_type !== PayloadReader::NO) {
                    throw ValidationException::withMessages(['resolution' => 'GCI precuration-ID export is for records without a precuration ID.']);
                }
                $row->fill($decision + ['resolved_gt_curation_id' => null]);
                $row->reviewed_by = $actor;
                $row->reviewed_at = now();
                // Review is informational. In particular it never queues or resets execution.
                $row->save();
            }
        });
    }
}
