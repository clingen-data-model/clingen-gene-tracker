<?php

namespace App\Http\Controllers\Api\client;

use App\Disease;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Validator;

class DiseaseController extends Controller
{

    use ApiResponse;
    /**
     * Called by GPM Controllers\Api\DiseaseLookupController.php
     */
    
    public function getDiseaseByMondoID(Request $request)
    {
         // Validate the mondo_id format
        $validator = Validator::make($request->all(), [
            'mondo_id' => ['required', 'regex:/^(MONDO:)\d{7}$/'],
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }
        
        $mondoId = $validator->validated()['mondo_id'];

        try {
            $disease = Disease::findByMondoIdOrFail($mondoId);
            return $this->successResponse($disease, 'Disease data found');
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Disease not found', 404);
        } catch (\Exception $e) {
            return $this->errorResponse('Server error', 500, $e->getMessage());
        }
    }

    public function search(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'query' => ['required', 'string', 'min:3'],
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        $query = strtolower($validator->validated()['query']);

        try {
            $results = Disease::search($query)->limit(250)->get();

            return $this->successResponse([
                'count'   => $results->count(),
                'results' => $results->toArray(),
            ]);
        } catch (Exception $e) {
            return $this->errorResponse('Internal server error', 500, $e->getMessage());
        }
    }


    /**
     * GPM app\Services\DiseaseLookup.php
     */
    public function getDiseaseByOntologyID(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ontology_id' => ['required', 'regex:/^(MONDO:|DOID:)\d+$/'],
        ]);        
        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }
        $ontologyId = $validator->validated()['ontology_id'];
        $ontology = strtolower(explode(':', $ontologyId)[0]);

        try {
            $disease = Disease::selectRaw("'" . $ontology . "' AS ontology, `" . $ontology . "_id` AS ontology_id, `name`")->where($ontology.'_id', $ontologyId)->first();
            return $this->successResponse($disease, 'Ontology data found');
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Ontology not found', 404);
        } catch (\Exception $e) {
            return $this->errorResponse('Server error', 500, $e->getMessage());
        }
    }
}
