<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\GciReconciliation\{Record, Review, Comparison, Decision, PrecurationExport, ActionExport};
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GciReconciliationController extends Controller
{
    public function index(Request $request)
    {
        $filters = $this->filters($request);
        $rows = Record::filtered($filters)->orderBy('id')->paginate(50);
        $rows->getCollection()->transform(fn ($row) => array_merge($row->toArray(), app(Comparison::class)->describe($row)));
        return $rows;
    }
    private function filters(Request $request): array
    {
        return $request->validate([
            'curation_id' => 'nullable|integer|min:1', 'gdm_uuid' => 'nullable|uuid',
            'include_retired' => 'sometimes|boolean', 'run_uuid' => 'nullable|uuid',
            'source_type' => 'nullable|string', 'match_status' => 'nullable|string', 'match_method' => 'nullable|string',
            'review_status' => 'nullable|string', 'resolution' => 'nullable|string', 'execution_status' => 'nullable|string',
            'gci_affiliation_id' => 'nullable|integer', 'gci_gene_symbol' => 'nullable|string|max:100',
            'has_identity_warning' => 'nullable|boolean', 'disease_match' => 'nullable|boolean', 'moi_match' => 'nullable|boolean',
            'gdm_uuid_match' => 'nullable|boolean', 'gene_match' => 'nullable|boolean', 'affiliation_match' => 'nullable|boolean',
            'gt_is_retired' => 'nullable|boolean', 'issue' => 'nullable|string|max:100', 'queue' => 'nullable|string',
        ]);
    }

    public function download(Request $request, string $type)
    {
        $scope = $request->validate(['scope' => ['required', Rule::in(['all', 'filtered'])]])['scope'];
        $filters = $this->filters($request);
        $query = $scope === 'filtered' ? Record::filtered($filters) : Record::query();
        $export = $type === 'actions' ? app(ActionExport::class) : app(PrecurationExport::class);
        try {
            $result = $export->payload($query->orderBy('id')->get());
        } catch (\RuntimeException $e) {
            throw \Illuminate\Validation\ValidationException::withMessages(['export' => $e->getMessage()]);
        }
        if (!$result['payload']['Count']) {
            throw \Illuminate\Validation\ValidationException::withMessages(['export' => 'No eligible records remain for this export and scope.']);
        }
        $filename = ($type === 'actions' ? 'gci-actions-' : 'gci-precuration-id-updates-').now()->format('Ymd-His').'.json';
        $json = json_encode($result['payload'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        return response()->streamDownload(function () use ($json) { echo $json; }, $filename, [
            'Content-Type' => 'application/json', 'Cache-Control' => 'private, no-store',
            'X-Export-Count' => (string) $result['payload']['Count'],
            'X-Export-Skipped' => (string) count($result['skipped']),
        ]);
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
