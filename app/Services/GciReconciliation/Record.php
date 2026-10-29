<?php
namespace App\Services\GciReconciliation;

use Illuminate\Database\Eloquent\Model;

class Record extends Model
{
    protected $table = 'gci_gt_reconciliations';
    protected $guarded = [];
    protected $casts = [
        'raw_payload' => 'array', 'issue_codes' => 'array', 'conflicting_gt_curation_ids' => 'array',
        'deleted_gt_curation_ids' => 'array', 'gt_is_retired' => 'boolean',
        'safe_update_candidate' => 'boolean', 'has_identity_warning' => 'boolean',
        'reviewed_at' => 'datetime', 'executed_at' => 'datetime', 'exported_at' => 'datetime',
    ];
    public function scopeFiltered($q, array $filters)
    {
        if (empty($filters['include_retired'])) $q->where('gt_is_retired', false);
        foreach (['run_uuid', 'source_type', 'match_status', 'match_method', 'review_status', 'resolution', 'execution_status',
            'gci_affiliation_id', 'gci_gene_symbol', 'has_identity_warning', 'disease_match', 'moi_match', 'gdm_uuid_match',
            'gene_match', 'affiliation_match', 'gt_is_retired'] as $key) {
            if (isset($filters[$key]) && $filters[$key] !== '') $q->where($key, $filters[$key]);
        }
        if (!empty($filters['issue'])) $q->whereJsonContains('issue_codes', $filters['issue']);
        switch ($filters['queue'] ?? '') {
            case 'not_found': $q->where('match_status', 'NOT_FOUND_IN_GT'); break;
            case 'uuid': $q->where(fn ($q) => $q->where('gdm_uuid_match', false)->orWhere('precuration_id_match', false)); break;
            case 'conflict': $q->whereIn('match_status', ['AMBIGUOUS_GDM_UUID', 'CONFLICT', 'ALREADY_RECONCILED']); break;
            case 'deleted': $q->where(fn ($q) => $q->where('precuration_id_exists_deleted', true)->orWhereNotNull('gt_deleted_at')->orWhereJsonLength('deleted_gt_curation_ids', '>', 0)); break;
            case 'safe': $q->where('safe_update_candidate', true)->where('source_type', PayloadReader::HAS)->where('match_method', 'PRECURATION_ID')->where('precuration_id_match', true)->where('gdm_uuid_match', true)->where('has_identity_warning', false); break;
            case 'export': $q->where('source_type', PayloadReader::NO)->whereNull('exported_at')->whereNotIn('execution_status', ['FAILED', 'NO_ACTION'])->where(fn ($q) => $q->where('match_status', 'CONFIRMED')->orWhereNotNull('resolved_gt_curation_id')); break;
            case 'review': $q->where('review_status', '!=', 'REVIEWED'); break;
        }
        return $q;
    }
}
