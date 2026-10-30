<?php
namespace App\Services\GciReconciliation;

final class Decision
{
    public const UPDATE = 'UPDATE_GT_DISEASE_MOI';
    public const UUID = 'UPDATE_GT_GDM_UUID';
    public const AFFILIATION = 'UPDATE_GT_AFFILIATION';
    public const MATCH = 'MATCH_EXISTING_GT_CURATION';
    public const CREATE = 'CREATE_GT_CURATION';
    public const EXPORT = 'UPDATE_GCI_PRECURATION_ID';
    public const NONE = ['NO_ACTION', 'HISTORICAL_NO_ACTION', 'KEEP_DELETED'];
    public const RESOLUTIONS = [self::UPDATE, self::UUID, self::AFFILIATION, self::MATCH, self::CREATE, self::EXPORT,
        'NO_ACTION', 'HISTORICAL_NO_ACTION', 'KEEP_DELETED', 'NEEDS_FURTHER_REVIEW'];
    public const REVIEW_STATUSES = ['PENDING', 'REVIEWED', 'NEEDS_FURTHER_REVIEW'];

    public static function affiliationEligible(Record $r): bool
    {
        return $r->source_type === PayloadReader::HAS && $r->match_status === 'CONFIRMED'
            && $r->is_confirmed_match && $r->match_method === 'PRECURATION_ID'
            && $r->precuration_id_match && $r->gdm_uuid_match && $r->gene_match
            && $r->affiliation_match !== null && !$r->affiliation_match
            && !$r->gdm_uuid_duplicate_in_gt && !$r->precuration_id_exists_deleted
            && !$r->gt_deleted_at && !$r->gt_is_retired && !$r->gt_archived_at
            && empty($r->conflicting_gt_curation_ids) && empty($r->deleted_gt_curation_ids)
            && !array_diff($r->issue_codes ?? [], [
                'AFFILIATION_MISMATCH', 'DISEASE_MISMATCH', 'MOI_MISMATCH', 'GCI_FREETEXT_DISEASE',
            ]);
    }
}
