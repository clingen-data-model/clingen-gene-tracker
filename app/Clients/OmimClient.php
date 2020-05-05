<?php

namespace App\Clients;

use GuzzleHttp\Client;
use App\Clients\Omim\OmimEntry;
use App\Clients\Omim\NullOmimEntry;
use App\Clients\Omim\OmimEntryContract;
use Illuminate\Support\Facades\Cache;
use App\Contracts\OmimClient as OmimClientContract;

/**
* Client for interacting with OMIM APi
*/
class OmimClient implements OmimClientContract
{
    protected $client;

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

    public function fetch($path, $query)
    {
        $cacheKey = sha1($path.'?'.http_build_query($query));
        return Cache::remember($cacheKey, 20*60, function () use ($path, $query) {
            $response = $this->client->request('GET', $path, $query);
            return json_decode($response->getBody()->getContents());
        });
    }

    /**
     * Fetches one or more entries from the OMIM API
     *
     * @param mixed $mimNumber integer or array or integers
     * @return OmimEntry omim entry
     */
    public function getEntry($mimNumber): OmimEntryContract
    {
        if (is_array($mimNumber)) {
            $mimNumber = implode(',', $mimNumber);
        }

        $query = $this->buildQuery([
            'mimNumber' => $mimNumber,
            'include'=>'geneMap'
        ]);
        $response = $this->fetch('entry', compact('query'));

        if (count($response->omim->entryList) > 0) {
            return new OmimEntry($response->omim->entryList[0]->entry);
        }
        return new NullOmimEntry();
    }

    public function search($searchData)
    {
        $query = $this->buildQuery($searchData);
        $response = $this->fetch('entry/search', compact('query'));

        return collect($response->omim->searchResponse->entryList)
                ->transform(function ($entry) {
                    return $entry->entry;
                });
    }

    public function geneSymbolIsValid($geneSymbol)
    {
        return $this->search([
            'search'=>'approved_gene_symbol:'.$geneSymbol
        ])->count() > 0;
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
