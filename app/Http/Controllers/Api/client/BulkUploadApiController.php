<?php 

namespace App\Http\Controllers\Api\client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Services\BulkCurationProcessor;
use App\Traits\ApiResponse;

class BulkUploadApiController extends Controller
{
    use ApiResponse;

    public function uploadJsonRows(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'expert_panel_id' => 'required',
            'rows' => 'required|array|min:1',
            'rows.*.gene_symbol' => 'required|string',
            'rows.*.curator_email' => 'required|email',
            'rows.*.curation_type' => 'string',
            'rows.*omim_id1' => '',
            'rows.*omim_id2' => '',
            'rows.*omim_id3' => '',
            'rows.*omim_id4' => '',
            'rows.*omim_id5' => '',
            'rows.*omim_id6' => '',
            'rows.*omim_id7' => '',
            'rows.*omim_id8' => '',
            'rows.*omim_id9' => '',
            'rows.*omim_id10' => '',
            'rows.*mondo_id' => '',
            'rows.*disease_entity_if_there_is_no_mondo_id' => '', 
            'rows.*rationale_1' => '', 
            'rows.*rationale_2' => '', 
            'rows.*rationale_3' => '', 
            'rows.*rationale_4' => '', 
            'rows.*rationale_Notes' => '', 
            'rows.*pmid_1' => '', 
            'rows.*pmid_2' => '', 
            'rows.*pmid_3' => '', 
            'rows.*pmid_4' => '', 
            'rows.*pmid_5' => '', 
            'rows.*pmid_6' => '', 
            'rows.*pmid_7' => '', 
            'rows.*pmid_8' => '', 
            'rows.*pmid_9' => '', 
            'rows.*pmid_10' => '', 
            'rows.*date_uploaded' => '',
            'rows.*precuration_date,' => '',
            'rows.*disease_entity_assigned_date,' => '',
            'rows.*curation_inprogress_date,' => '',
            'rows.*curation_provisional_date,' => '',
            'rows.*curation_approved_date' => '',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());            
        }

        $expertPanelId = $request->expert_panel_id;
        $rows = $request->rows;
        $processor = new BulkCurationProcessor();

        $results = collect();
        foreach ($rows as $index => $row) {
            try {
                $curation = $processor->processRow($row, $expertPanelId, $index + 1);
                $results->push([
                    'row' => $index + 1,
                    'status' => 'success',
                    'curation_id' => $curation->id,
                    'gene_symbol' => $curation->gene_symbol,
                ]);
            } catch (\Exception $e) {
                return $this->errorResponse('Server error', 500, $e->getMessage());
            }
        }

        return $this->successResponse($results, 'Bulk rows processed');        
    }
}
