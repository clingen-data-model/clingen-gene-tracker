<?php
namespace App\Services\GciReconciliation;

final class Decision
{
    public const UPDATE = 'UPDATE_GT_DISEASE_MOI';
    public const UUID = 'UPDATE_GT_GDM_UUID';
    public const MATCH = 'MATCH_EXISTING_GT_CURATION';
    public const CREATE = 'CREATE_GT_CURATION';
    public const EXPORT = 'UPDATE_GCI_PRECURATION_ID';
    public const NONE = ['NO_ACTION', 'HISTORICAL_NO_ACTION', 'KEEP_DELETED'];
    public const RESOLUTIONS = [self::UPDATE, self::UUID, self::MATCH, self::CREATE, self::EXPORT,
        'NO_ACTION', 'HISTORICAL_NO_ACTION', 'KEEP_DELETED', 'NEEDS_FURTHER_REVIEW'];
    public const REVIEW_STATUSES = ['PENDING', 'REVIEWED', 'NEEDS_FURTHER_REVIEW'];
}
