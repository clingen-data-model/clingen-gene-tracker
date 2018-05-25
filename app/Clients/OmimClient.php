<?php

namespace App\Clients;

use App\Contracts\OmimClient as OmimClientContract;
use GuzzleHttp\Client;

/**
* Client for interacting with OMIM APi
*/
class OmimClient implements OmimClientContract
{
    protected $client;
    protected $query;

    public function __construct($client = null)
    {
        if ($client && get_class($client) != Client::class) {
            throw new \Exception('Bad client exception');
        }

        $this->client = $client;
        if (!$this->client) {
            $this->client = $this->getClient();
        }

        $this->baseQuery = ['format'=>'json'];
    }

    public function getEntry($mimNumber)
    {
        if (is_array($mimNumber)) {
            $mimNumber = implode(',', $mimNumber);
        }
        $query = $this->buildQuery(compact('mimNumber'));
        $response = $this->client->request('GET', 'entry', compact('query'));
        $response = json_decode($response->getBody()->getContents());

        return $response->omim->entryList;
    }

    public function search($searchData)
    {
        $query = $this->buildQuery($searchData);
        $response = $this->client->request('GET', 'entry/search', compact('query'));
        $response = json_decode($response->getBody()->getContents());

        return collect($response->omim->searchResponse->entryList)
                ->transform(function ($entry) {
                    return $entry->entry;
                });
    }

    public function getGenePhenotypes($geneSymbol)
    {
        $entryList = $this->search([
                    'search'=>'approved_gene_symbol:'.$geneSymbol,
                    'include'=> 'geneMap'
                ]);
        if ($this->responseHasPhenotypeMapList($entryList)) {
            return collect($entryList[0]->geneMap->phenotypeMapList)
                    ->transform(function ($item) {
                        return $item->phenotypeMap;
                    });
        }

        return collect([]);
    }

    private function buildQuery($params)
    {
        return array_merge($this->baseQuery, $params);
    }

    private function getClient()
    {
        return new Client([
            'base_uri'=>'https://api.omim.org/api/',
            'headers'=>[
                'ApiKey' => config('app.omim_key')
            ]
        ]);
    }

    private function responseHasPhenotypeMapList($entryList)
    {
        return count($entryList) > 0
                && isset($entryList[0]->geneMap->phenotypeMapList);
    }
}
