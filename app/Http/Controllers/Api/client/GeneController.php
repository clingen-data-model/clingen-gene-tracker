<?php

namespace App\Http\Controllers\Api\client;

use App\Gene;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\BulkLookupRequest;
use App\Http\Resources\CurationSimpleResource;
use App\Services\Curations\CurationSearchService;

class GeneController extends Controller
{

    use ApiResponse;    

    /**
     * Display the specified resource.
     * Called by: GPM Controllers\Api\GeneLookupController.php
     */
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
            $results = Gene::where('gene_symbol', 'LIKE', '%'.$query.'%')
                            ->orWhere('hgnc_id', 'LIKE', '%'.$query.'%')
                            ->limit(50)
                            ->get();

            return $this->successResponse([
                'count'   => $results->count(),
                'results' => $results->toArray(),
            ]);
        } catch (Exception $e) {
            return $this->errorResponse('Internal server error', 500, $e->getMessage());
        }
    }    

    /**
     * GPM app\Services\HgncLookup.php
     */

    public function getGeneSymbolByID(Request $request)
    {
        // Validate the input
        $validator = Validator::make($request->all(), [
            'hgnc_id' => ['required', 'integer', 'min:1'],
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        $hgncID = (int) $validator->validated()['hgnc_id'];

        try {
            $geneData = Gene::select('gene_symbol')
                        ->where('hgnc_id', $hgncID)
                        ->first();

            if (!$geneData) {
                return $this->errorResponse("No gene with HGNC ID {$hgncID} found in our records.", 404);
            }

            return $this->successResponse([
                'hgnc_id' => $hgncID,
                'gene_symbol' => $geneData->gene_symbol,
            ], 'Gene found');
        } catch (Exception $e) {
            return $this->errorResponse('Internal server error', 500, $e->getMessage());
        }
    }

    public function getGeneSymbolBySymbol(Request $request)
    {
        // Validate the input
        $validator = Validator::make($request->all(), [
            'gene_symbol' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        $geneSymbol = strtoupper(trim($validator->validated()['gene_symbol']));

        try {
            $geneData = Gene::select('hgnc_id')
                        ->where('gene_symbol', $geneSymbol)
                        ->first();

            if (!$geneData) {
                return $this->errorResponse("No gene with HGNC ID {$geneSymbol} found in our records.", 404);
            }

            return $this->successResponse([
                'hgnc_id' => $geneData->hgnc_id,
                'gene_symbol' => $geneSymbol,
            ], 'Gene found');
        } catch (Exception $e) {
            return $this->errorResponse('Internal server error', 500, $e->getMessage());
        }
    }

    public function geneCurationSearch(BulkLookupRequest $request, CurationSearchService $search)
    {
        try {
            $validated = $request->validated();

            $validated['perPage'] = 150;

            $results = $search->search($validated)
                ->map(function ($curation) {
                    $curation->available_phenotypes = $curation->gene->phenotypes;
                    return $curation;
                });

            if ($results->isEmpty()) {
                return $this->errorResponse(
                    'Validation failed', 422, 
                    ['gene_symbol' => ["There were no results for your search. Are you sure you're using valid HGNC gene symbols? Could the gene symbol(s) you searched be aliases or previously used symbols?"]]
                );
            }
            
            return $this->successResponse(
                CurationSimpleResource::collection($results), 
                'Curation data found'
            );
        } catch (\Throwable $e) {
            return $this->errorResponse('Server error', 500, $e->getMessage());
        }
    }
}
