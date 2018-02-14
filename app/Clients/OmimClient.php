<?php
namespace App\Clients;

use GuzzleHttp\Client;

/**
* Client for interacting with OMIM APi
*/
class OmimClient
{
    protected $client;
    protected $query;

    public function __construct(Client $client = null)
    {
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
        return $response->omim->searchResponse->entryList;
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
}
