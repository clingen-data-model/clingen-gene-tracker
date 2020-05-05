<?php

namespace App\Http\Controllers\Api;

use App\Clients\OmimClient as Omim;
use App\Contracts\OmimClient;
use App\Http\Controllers\Controller;
use App\Http\Requests\OmimGeneRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OmimController extends Controller
{
    protected $omim;

    public function __construct(OmimClient $client)
    {
        $this->omim = $client;
    }

    public function entry(Request $request)
    {
        if (!$request->mim_number) {
            return new JsonResponse(['errors' => ['You must provide a mim_number to get a omim record.']], 422);
        }
        $entry = $this->omim->getEntry($request->mim_number);
        return $entry->toArray();
    }

    public function search(Request $request)
    {
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

        if (!$this->omim->geneSymbolIsValid($geneSymbol)) {
            return new JsonResponse(['errors' => ['No HGNC gene symbol was found for '.$geneSymbol]], 404);
        }

        $searchResults = $this->omim->getGenePhenotypes($geneSymbol);
        return [
            'gene_symbol' => $geneSymbol,
            'phenotypes' => $searchResults
        ];
    }
}
