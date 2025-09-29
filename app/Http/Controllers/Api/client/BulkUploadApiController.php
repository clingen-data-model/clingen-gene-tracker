<?php 

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Services\BulkCurationProcessor;
use App\Traits\ApiResponse;
use App\Services\ExpertPanelResolver;
use Illuminate\Support\Facades\Log;

class BulkUploadApiController extends Controller
{
    use ApiResponse;

    public function uploadJsonRows(Request $request)
    {
        ignore_user_abort(true);          // keep running if client drops
        set_time_limit(600);              // or 0 for "no limit" (be careful)
        ini_set('max_execution_time', '600');

        $validator = Validator::make($request->all(), [
            'affiliation_id' => 'required',
            'rows' => 'required|array|min:1',
            'rows.*.gene_symbol' => 'required|string',
            'rows.*.curator_email' => 'nullable|email',
            'rows.*.curation_type' => 'nullable|string',
            'rows.*.omim_id1' => 'nullable',
            'rows.*.omim_id2' => 'nullable',
            'rows.*.omim_id3' => 'nullable',
            'rows.*.omim_id4' => 'nullable',
            'rows.*.omim_id5' => 'nullable',
            'rows.*.omim_id6' => 'nullable',
            'rows.*.omim_id7' => 'nullable',
            'rows.*.omim_id8' => 'nullable',
            'rows.*.omim_id9' => 'nullable',
            'rows.*.omim_id10' => 'nullable',
            'rows.*.mondo_id' => 'nullable',
            'rows.*.disease_entity_if_there_is_no_mondo_id' => 'nullable', 
            'rows.*.rationale_1' => 'nullable', 
            'rows.*.rationale_2' => 'nullable', 
            'rows.*.rationale_3' => 'nullable', 
            'rows.*.rationale_4' => 'nullable', 
            'rows.*.rationale_notes' => 'nullable', 
            'rows.*.pmid_1' => 'nullable', 
            'rows.*.pmid_2' => 'nullable', 
            'rows.*.pmid_3' => 'nullable', 
            'rows.*.pmid_4' => 'nullable', 
            'rows.*.pmid_5' => 'nullable', 
            'rows.*.pmid_6' => 'nullable', 
            'rows.*.pmid_7' => 'nullable', 
            'rows.*.pmid_8' => 'nullable', 
            'rows.*.pmid_9' => 'nullable', 
            'rows.*.pmid_10' => 'nullable', 
            'rows.*.date_uploaded' => 'nullable',
            'rows.*.precuration_date,' => 'nullable',
            'rows.*.disease_entity_assigned_date,' => 'nullable',
            'rows.*.curation_inprogress_date,' => 'nullable',
            'rows.*.curation_provisional_date,' => 'nullable',
            'rows.*.curation_approved_date' => 'nullable',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());            
        }

        $clingenId = (int) $request->input('affiliation_id');
        if (! $clingenId) { return $this->errorResponse('Affiliation ID is required for Gene Bulk Upload', 422); }

        try {
            $resolved = app(ExpertPanelResolver::class)->ensureForClinGen($clingenId, 'Gene Bulk Upload');
        } catch (\RuntimeException $e) {
            return $this->errorResponse('Affiliation not found', 404, ['affiliation_id' => $clingenId]);
        }

        $epID   = $resolved['expert_panel_id'];
        $aff    = $resolved['affiliation']; // Affiliation object

        $rows = collect($request->input('rows', []))
            ->map(function ($row) {
                if (isset($row['gene_symbol'])) {
                    $row['gene_symbol'] = strtoupper(trim($row['gene_symbol']));
                }
                return $row;
            })->values()->all();
            
        $processor = new BulkCurationProcessor();
        Log::debug('Processing '.count($rows).' rows for EP '.$epID.' (Aff '.$aff->name.' #'.$aff->clingen_id.')');
                $results = collect();
        foreach ($rows as $index => $row) {
            try {
                $row = is_array($row) ? $row : (array) $row;
                Log::debug('Row ' . $index . ': ', $row);
                $curation = $processor->processRow($row, $epID, $index + 1);
                $results->push([
                    'row' => $index + 1,
                    'status' => 'success',
                    'curation_id' => $curation->id,
                    'gene_symbol' => $curation->gene_symbol,
                ]);
            } catch (\Exception $e) {
                return $this->errorResponse('Failed to save the genes for Gene Bulk Upload on Genetracker', 500, $e->getMessage());
            }
        }
        
        return $this->successResponse($results, 'Bulk rows processed');
    }
}
