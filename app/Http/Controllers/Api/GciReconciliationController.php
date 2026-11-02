<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\GciReconciliation\{Record, Review, Comparison, Decision};
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GciReconciliationController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'curation_id' => 'nullable|integer|min:1', 'gdm_uuid' => 'nullable|uuid',
            'include_retired' => 'sometimes|boolean', 'run_uuid' => 'nullable|uuid',
            'source_type' => 'nullable|string', 'match_status' => 'nullable|string', 'match_method' => 'nullable|string',
            'review_status' => 'nullable|string', 'resolution' => 'nullable|string', 'execution_status' => 'nullable|string',
            'gci_affiliation_id' => 'nullable|integer', 'gci_gene_symbol' => 'nullable|string|max:100',
            'has_identity_warning' => 'nullable|boolean', 'disease_match' => 'nullable|boolean', 'moi_match' => 'nullable|boolean',
            'gdm_uuid_match' => 'nullable|boolean', 'gene_match' => 'nullable|boolean', 'affiliation_match' => 'nullable|boolean',
            'gt_is_retired' => 'nullable|boolean', 'issue' => 'nullable|string|max:100', 'queue' => 'nullable|string',
        ]);
        $rows = Record::filtered($filters)->orderBy('id')->paginate(50);
        $rows->getCollection()->transform(fn ($row) => array_merge($row->toArray(), app(Comparison::class)->describe($row)));
        return $rows;
    }
    public function show(int $id, Comparison $comparison)
    {
        $row = Record::findOrFail($id);
        return ['record' => array_merge($row->toArray(), $comparison->describe($row)), 'candidates' => $comparison->candidates($row)];
    }

    public function review(Request $request, Review $review)
    {
        $ids = $request->validate(['ids' => 'required|array|min:1|max:500', 'ids.*' => 'required|integer|distinct|exists:gci_gt_reconciliations,id'])['ids'];
        $data = $request->validate(['review_status' => ['required', Rule::in(Decision::REVIEW_STATUSES)],
            'resolution' => ['nullable', Rule::in(Decision::RESOLUTIONS)], 'review_notes' => 'nullable|string|max:10000',
            'resolved_gt_curation_id' => 'nullable|integer|min:1|exists:curations,id']);
        $review->save($ids, $data, $request->user()->id);
        return response()->noContent();
    }
}
