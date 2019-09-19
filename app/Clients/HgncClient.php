<?php

namespace App\Clients;

use App\HgncRecord;
use GuzzleHttp\Client;
use App\Exceptions\HttpNotFoundException;
use App\Contracts\HgncClient as HgncClientContract;
use App\Exceptions\HttpUnexpectedResponseException;

class HgncClient implements HgncClientContract
{
    protected $guzzleClient;

    public function __construct(Client $guzzleClient)
    {
        $this->guzzleClient = $guzzleClient;
    }

    public function fetchGeneSymbol(string $geneSymbol):object
    {
        $response = $this->guzzleClient->request('GET', '/fetch/symbol/'.$geneSymbol);
        $responseObj = json_decode($response->getBody()->getContents());
        if ($responseObj->response->numFound == 0) {
            throw new HttpNotFoundException('Gene symbol '.$geneSymbol.' not found in HGNC API.');
        }

        if ($responseObj->response->numFound > 1) {
            throw new HttpUnexpectedResponseException('Search for gene symbol '.$geneSymbol.'resulted in '.$responseObj->response->numFound.' records found in HGNC API.');
        }

        return new HgncRecord($responseObj->response->docs[0]);

    }

    
}
