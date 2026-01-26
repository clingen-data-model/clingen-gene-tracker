<?php

namespace App\Http\Controllers\Api;

use App\Gene;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Response;
use App\Http\Requests\BulkLookupRequest;
use App\Http\Resources\CurationSimpleResource;
use App\Services\Curations\CurationSearchService;
use Illuminate\Support\Facades\Validator;

class GeneController extends Controller
{
    public function index(Request $request)
    {
        return $this->search($request);
    }
 
    
    public function download(Request $request)
    {
        $headers = array(
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=file.csv",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        );
    
        $results = $this->search($request)
                    ->transform(function ($gene) {
                        if ($gene->phenotypes->count() == 0) {
                            return collect([[
                                'Gene' => $gene->gene_symbol,
                                'Phenotype' => null,
                                'MOI' => null
                            ]]);
                        }
                        return $gene->phenotypes->map(function ($pheno, $key) use ($gene) {
                            $phenoName = $pheno->obsolete ? $pheno->name . ' [Not in latest OMIM]' : $pheno->name;
                            return [
                                'Gene' => $gene->gene_symbol,
                                'Phenotype' => $phenoName,
                                'Phenotype MIM Number' => $pheno->mim_number,
                                'MOI' => $pheno->moi
                            ];
                        });
                    })->flatten(1);

        $columns = ['Gene', 'Phenotype', 'Phenotype MIM Number','MOI'];
        $callback = function () use ($results, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
    
            foreach ($results as $result) {
                fputcsv(
                    $file,
                    $result
                );
            }
            fclose($file);
        };
        return Response::stream($callback, 200, $headers);
    }

    private function search(Request $request): Collection
    {
        $query = Gene::query();
        if ($request->where) {
            foreach ($request->where as $key => $value) {
                if (is_string($value)) {
                    $value = explode(',',$value);
                }
                $value = array_filter(array_map(function ($i) { return trim($i); }, $value), function ($i) {
                    return !empty($i);
                });
                $query->whereIn($key, $value);
            }
        }
        if ($request->with) {
            $query->with($request->with);
        }
        if ($request->orderBy) {
            foreach ($request->orderBy as $orderBy) {
                $this->query->orderBy(...$orderBy);
            }
        }

        return $query->get();
    }

    public function searchPost(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'query' => ['string'],
            'limit' => ['nullable', 'integer', 'min:10', 'max:50'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $query = strtolower($validator->validated()['query']);
        $limit = $validator->validated()['limit'] ?? 10;

        if( strlen($query) < 3) {
            return [];
        }
        
        return Gene::where('gene_symbol', 'LIKE', '%'.$query.'%')
                        ->orWhere('hgnc_id', 'LIKE', '%'.$query.'%')
                        ->limit($limit)
                        ->get();
    }    

    public function getGeneSymbolByID(Request $request)
    {
        // Validate the input
        $validator = Validator::make($request->all(), [
            'hgnc_id' => ['required', 'integer', 'min:1'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $hgncID = (int) $validator->validated()['hgnc_id'];
        
        return Gene::select('hgnc_id', 'gene_symbol', 'omim_id', 'hgnc_name', 'hgnc_status', 'previous_symbols', 'alias_symbols')
                    ->where('hgnc_id', $hgncID)
                    ->firstOrFail();
    }

    public function getGeneSymbolBySymbol(Request $request)
    {        
        // Validate the input
        $validator = Validator::make($request->all(), [
            'gene_symbol' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $geneSymbol = strtoupper(trim($validator->validated()['gene_symbol']));

        return Gene::select('hgnc_id', 'gene_symbol', 'omim_id', 'hgnc_name', 'hgnc_status', 'previous_symbols', 'alias_symbols')
                    ->where('gene_symbol', $geneSymbol)
                    ->firstOrFail();
                   
    }

    public function geneCurationSearch(BulkLookupRequest $request, CurationSearchService $search)
    {
        $validated = $request->validated();

        $validated['perPage'] = 1500; // Set a default perPage value. There's an application that has gene aver 1200 genes

        $results = $search->search($validated)
            ->map(function ($curation) {
                $curation->available_phenotypes = $curation->gene->phenotypes;
                return $curation;
            });

        if ($results->isEmpty()) {
            return [];
        }
        
        return CurationSimpleResource::collection($results);        
    }
    
}
