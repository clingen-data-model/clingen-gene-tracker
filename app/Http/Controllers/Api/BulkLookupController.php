<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\BulkLookupRequest;
use App\Http\Resources\CurationResource;
use App\Http\Resources\CurationSimpleResource;
use Illuminate\Support\Facades\Response;
use App\Services\Curations\CurationSearchService;
use Illuminate\Validation\ValidationException;

class BulkLookupController extends Controller
{
    protected $search;

    public function __construct(CurationSearchService $search)
    {
        $this->search = $search;
    }

    public function data(BulkLookupRequest $request)
    {
        $useSimple = $request->input('resource') === 'simple';
        $Resource = $useSimple ? CurationSimpleResource::class : CurationResource::class;
        
        $results = $this->search->search($request->all());
        if ($results->count() == 0) {
            throw ValidationException::withMessages(['gene_symbols' => ['There were no results for your search.  Are you sure you\'re using valid HGNC gene symbols? Could the gene symbol(s) you searched be aliases or previously used symbols?']]);
        }

        $attach = function ($curation) {
            $curation->available_phenotypes = optional($curation->gene)->phenotypes;
            return $curation;
        };
        $results = $results->map($attach);
        return $Resource::collection($results);
    }
    
    public function download(BulkLookupRequest $request)
    {
        $headers = array(
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=file.csv",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        );
    
        $results = $this->search->search($request->all())
                    ->transform(function ($curation) {
                        return [
                            'Gene' => $curation->gene_symbol,
                            'MonDO ID' => $curation->mondo_id,
                            'Disease entity' => $curation->mondo_name,
                            'Expert panel' => $curation->expertPanel->name,
                            'Classificaton' => ($curation->currentClassification) 
                                                    ? $curation->currentClassification->name 
                                                    : null,
                            'Classificaton date' => ($curation->currentClassification && $curation->currentClassification->pivot) 
                                ? $curation->currentClassification->pivot->classification_date 
                                : null,
                            'Curation type' => ($curation->curationType) 
                                                ? $curation->curationType->description 
                                                : null,
                            'Rationales' => $curation->rationales->map(function ($r) {
                                return $r->name;
                            })->join("; "),
                            'Status' => ($curation->currentStatus) 
                                            ? $curation->currentStatus->name 
                                            : null,
                            'Status date' => ($curation->currentStatus && $curation->currentStatus->pivot) 
                                                ? $curation->currentStatus->pivot->status_date 
                                                : null,
                            'Last updated' => $curation->updated_at->format('Y-m-d H:i:s'),
                            'Curated Phenotypes' => $curation->phenotypes->map(function ($ph) {
                                return $ph->name.' ('.$ph->mim_number.')';
                            })->join("\n"),
                            'Available Phenotypes' => $curation->gene->phenotypes->map(function ($ph) {
                                return $ph->name.' ('.$ph->mim_number.')';
                            })->join("; ")
                        ];
                    });
        if ($results->count() == 0) {
            throw ValidationException::withMessages(['gene_symbols' => ['There were no results for your search.  Are you sure you\'re using valid HGNC gene symbols?']]);
        }
        $columns = array_keys($results->first());
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
}
