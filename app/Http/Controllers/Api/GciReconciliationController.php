<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\GciReconciliation\{Run, Record, Workflow, Decision, PayloadReader, GciReconciliationExecutor};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class GciReconciliationController extends Controller
{
    public function runs() { return Run::with('uploads')->latest()->limit(200)->get(); }
    public function createRun(Request $request)
    {
        $data = $request->validate(['label' => 'nullable|string|max:255']);
        return Run::create($data + ['uuid' => (string) Str::uuid(), 'uploaded_by' => $request->user()->id, 'status' => 'DRAFT']);
    }
    public function upload(Request $request, string $uuid, Workflow $workflow)
    {
        $data = $request->validate(['file' => 'required|file|max:20480', 'source_type' => ['required', Rule::in([PayloadReader::HAS, PayloadReader::NO])]]);
        return $workflow->upload(Run::findOrFail($uuid), $request->file('file'), $data['source_type'], $request->user()->id);
    }
    public function reconcile(Request $request, string $uuid, Workflow $workflow)
    {
        return $workflow->reconcile(Run::findOrFail($uuid), $request->user()->id);
    }
    public function index(Request $request)
    {
        $filters = $request->validate([
            'include_retired' => 'sometimes|boolean', 'run_uuid' => 'nullable|uuid',
            'source_type' => 'nullable|string', 'match_status' => 'nullable|string', 'match_method' => 'nullable|string',
            'review_status' => 'nullable|string', 'resolution' => 'nullable|string', 'execution_status' => 'nullable|string',
            'gci_affiliation_id' => 'nullable|integer', 'gci_gene_symbol' => 'nullable|string|max:100',
            'has_identity_warning' => 'nullable|boolean', 'disease_match' => 'nullable|boolean', 'moi_match' => 'nullable|boolean',
            'gdm_uuid_match' => 'nullable|boolean', 'gene_match' => 'nullable|boolean', 'affiliation_match' => 'nullable|boolean',
            'gt_is_retired' => 'nullable|boolean', 'issue' => 'nullable|string|max:100', 'queue' => 'nullable|string',
        ]);
        return Record::filtered($filters)->orderBy('id')->paginate(50);
    }
    public function show(int $id, Workflow $workflow)
    {
        $row = Record::findOrFail($id);
        return ['record' => $row, 'candidates' => $workflow->candidates($row),
            'history' => DB::table('gci_reconciliation_audits')->where('reconciliation_id', $id)->orderBy('id')->get()];
    }
    public function review(Request $request, GciReconciliationExecutor $executor)
    {
        $ids = $this->ids($request);
        $data = $request->validate(['review_status' => ['required', Rule::in(Decision::REVIEW_STATUSES)],
            'resolution' => ['nullable', Rule::in(Decision::RESOLUTIONS)], 'review_notes' => 'nullable|string|max:10000',
            'resolved_gt_curation_id' => 'nullable|integer|min:1|exists:curations,id']);
        $executor->review($ids, $data, $request->user()->id);
        return response()->noContent();
    }
    public function preview(Request $request, GciReconciliationExecutor $executor)
    {
        return $executor->preview($this->ids($request), $request->user()->id);
    }
    public function execute(Request $request, GciReconciliationExecutor $executor)
    {
        $data = $request->validate(['preview_uuid' => 'required|uuid']);
        return $executor->execute($data['preview_uuid'], $request->user()->id);
    }
    public function export(Request $request, Workflow $workflow)
    {
        $payload = $workflow->export($this->ids($request), $request->user()->id);
        return response()->json($payload, 200, ['Content-Disposition' => 'attachment; filename="gdmHasPreCurId.json"'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
    private function ids(Request $request): array
    {
        return $request->validate(['ids' => 'required|array|min:1|max:500', 'ids.*' => 'required|integer|distinct|exists:gci_gt_reconciliations,id'])['ids'];
    }
}
