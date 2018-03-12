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
        if (!$request->has('mim_number')) {
            return new JsonResponse(['errors' => ['You must provide a mim_number to get a omim record.']], 422);
        }
        $entry = $this->omim->getEntry($request->mim_number);
        return $entry;
    }

    public function search(Request $request)
    {
        if (!$request->has('search')) {
            return new JsonResponse(['errors' => ['You must provide a search term to search OMIM.']], 422);
        }

        $searchResults = $this->omim->search($request->all());
        return $searchResults;
    }

    public function gene(Request $request)
    {
        if (!$request->has('gene_symbol')) {
            return new JsonResponse(['errors' => ['You must provide a gene_symbol to get the gene\'s phenotypes.']], 422);
        }

        $searchResults = $this->omim->getGenePhenotypes($request->gene_symbol);
        return [
            'gene_symbol' => $request->gene_symbol,
            'phenotypes' => $searchResults
        ];
    }
}
