<?php

namespace App\Http\Controllers\Api;

use Log;
use App\Gene;
use App\Curation;
use Illuminate\Http\Request;
use App\Contracts\OmimClient;
use Illuminate\Http\JsonResponse;
use App\Clients\OmimClient as Omim;
use App\Http\Controllers\Controller;
use App\Http\Requests\OmimGeneRequest;

class OmimController extends Controller
{
    protected $omim;

    public function __construct(OmimClient $client)
    {
        $this->omim = $client;
    }

    /**
     * @deprecated
     */
    public function entry(Request $request)
    {
        Log::warning('OmimController::entry has been deprecated.');
        if (!$request->mim_number) {
            return new JsonResponse(['errors' => ['You must provide a mim_number to get a omim record.']], 422);
        }
        $entry = $this->omim->getEntry($request->mim_number);
        return $entry->toArray();
    }

    /**
     * @deprecated
     */
    public function search(Request $request)
    {
        Log::warning('OmimController::search has been deprecated.');
        if (!$request->has('search')) {
            return new JsonResponse(['errors' => ['You must provide a search term to search OMIM.']], 422);
        }

        $searchResults = $this->omim->search($request->all());
        return $searchResults;
    }

    public function gene($geneSymbol)
    {
        if (!$geneSymbol) {
            return new JsonResponse(['errors' => ['You must provide a gene_symbol to get the gene\'s phenotypes.']], 422);
        }

        $gene = Gene::findBySymbol($geneSymbol);
        if (!$gene) {
            return new JsonResponse(['errors' => ['No HGNC gene symbol was found for '.$geneSymbol]], 404);
        }

        return [
            'gene_symbol' => $geneSymbol,
            'phenotypes' => $gene->phenotypes->map(fn($ph) => $this->serializePhenotypeModelForResponse($ph))
        ];
    }

    public function forCuration($curationId)
    {
        $curation = Curation::findOrFail($curationId);

        $curationPhenotypes = $curation->phenotypes;

        $phenotypes = $curationPhenotypes
            ->merge($curation->gene->phenotypes)
            ->unique('id')
            ->sortBy(fn($ph) => $ph->id)
            ->values();

        return [
            'gene_symbol' => $curation->gene_symbol,
            'phenotypes' => $phenotypes->map(fn($ph) => $this->serializePhenotypeModelForResponse($ph)),
        ];
    }

    private function serializePhenotypeModelForResponse($phenotype):array
    {
        return [
            'phenotype' => $phenotype->name,
            'phenotypeMimNumber' => $phenotype->mim_number,
            'phenotypeInheritance' => $phenotype->moi,
        ];
    }
}