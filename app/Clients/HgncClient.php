<?php

namespace App\Clients;

use App\HgncRecord;
use GuzzleHttp\Client;
use App\Contracts\GeneSymbolUpdate as GeneSymbolUpdateContract;
use App\Exceptions\HttpNotFoundException;
use App\Contracts\HgncClient as HgncClientContract;
use App\Exceptions\HttpUnexpectedResponseException;
use App\ValueObjects\GeneSymbolUpdate;

class HgncClient implements HgncClientContract
{
    protected $guzzleClient;

    public function __construct(Client $guzzleClient)
    {
        $this->guzzleClient = $guzzleClient;
    }

    public function fetch($key, $value):HgncRecord
    {
        $response = $this->guzzleClient->request('GET', '/fetch/'.$key.'/'.$value);
        $responseObj = json_decode($response->getBody()->getContents());
        if ($responseObj->response->numFound == 0) {
            throw new HttpNotFoundException($key.' '.$value.' not found in HGNC API.');
        }

        if ($responseObj->response->numFound > 1) {
            throw new HttpUnexpectedResponseException('Search for '.$key.' '.$value.'resulted in '.$responseObj->response->numFound.' records found in HGNC API.');
        }

        return new HgncRecord($responseObj->response->docs[0]);
    }

    public function fetchHgncId(string $hgncId):HgncRecord
    {
        return $this->fetch('hgnc_id', $hgncId);
    }

    public function fetchGeneSymbol(string $geneSymbol):HgncRecord
    {
        return $this->fetch('symbol', $geneSymbol);
    }

    public function fetchPreviousSymbol(string $geneSymbol):HgncRecord
    {
        return $this->fetch('prev_symbol', $geneSymbol);
    }
}