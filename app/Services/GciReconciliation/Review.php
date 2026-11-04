<?php
namespace App\Services\GciReconciliation;

use App\Curation;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class Review
{
    public function save(array $ids, array $decision, int $actor): void
    {
        $errors = [];
        DB::transaction(function () use ($ids, $decision, $actor, &$errors) {
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
                if (($decision['resolution'] ?? '') === Decision::EXPORT && $row->source_type !== PayloadReader::NO && !$resolved) {
                    throw ValidationException::withMessages(['resolved_gt_curation_id' => 'Select the correct GT curation to correct an existing GCI precuration ID.']);
                }
                $row->fill($decision + ['resolved_gt_curation_id' => null]);
                $row->reviewed_by = $actor;
                $row->reviewed_at = now();
                $synchronized = false;
                try {
                    $synchronized = app(SafeUpdates::class)->synchronizeReview($row);
                } catch (\Throwable $e) {
                    $row->execution_status = 'NOT_READY';
                    $row->execution_error = $e->getMessage();
                    $row->save();
                    $errors['records.'.$row->id] = 'Review saved, but disease/MoI synchronization failed: '.$e->getMessage();
                    continue;
                }
                // Completion tracks the live result; original findings remain immutable.
                if (!in_array($row->execution_status, ['APPLIED', 'NO_ACTION'], true)) {
                    if (in_array($row->resolution, Decision::GCI_ACTIONS, true)) {
                        // Completion is the reviewed GCI decision, not a GT execution.
                        $row->execution_status = 'NOT_READY';
                    } elseif ($row->review_status === 'REVIEWED' && app(Comparison::class)->describe($row)['current_matches_gci']) {
                        $snapshotMatched = $row->match_status === 'CONFIRMED' && !$row->has_identity_warning
                            && $row->gdm_uuid_match && $row->gene_match && $row->affiliation_match
                            && $row->disease_match && $row->moi_match
                            && ($row->source_type === PayloadReader::NO || $row->precuration_id_match)
                            && (!$row->resolved_gt_curation_id || $row->resolved_gt_curation_id == $row->gt_curation_id);
                        $row->execution_error = null;
                        $row->execution_status = !$synchronized && !app(ManualTarget::class)->selected($row) && $snapshotMatched ? 'NO_ACTION' : 'RESOLVED_MANUALLY';
                    } elseif ($row->review_status === 'REVIEWED' || $row->execution_status === 'RESOLVED_MANUALLY') {
                        // Reopening a review or finding a new discrepancy withdraws manual completion.
                        $row->execution_status = 'NOT_READY';
                    }
                }
                $row->save();
            }
        });
        if ($errors) throw ValidationException::withMessages($errors);
    }
}
