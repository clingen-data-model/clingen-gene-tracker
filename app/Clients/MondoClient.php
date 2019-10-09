<?php

namespace App\Clients;

use App\MondoRecord;
use GuzzleHttp\Client;
use App\Contracts\MondoClient as MondoClientContract;
use App\Exceptions\HttpNotFoundException;
use GuzzleHttp\Exception\ClientException;

class MondoClient implements MondoClientContract
{
    protected $guzzleClient;

    public function __construct(Client $guzzleClient)
    {
        $this->guzzleClient = $guzzleClient;
    }

    public function fetchRecord($mondoId):MondoRecord
    {
        try {
            $guzzleResponse = $this->guzzleClient->get('terms/http%253A%252F%252Fpurl.obolibrary.org%252Fobo%252FMONDO_'.$mondoId);
            $responseObj = json_decode($guzzleResponse->getBody()->getContents());
            return new MondoRecord($responseObj);
        } catch (ClientException $th) {
            if ($th->getResponse()->getStatusCode() == 404) {
                throw new HttpNotFoundException('MonDO ID '.$mondoId.' was not found using the MonDO API', $th->getCode(), $th);
            }
            throw $th;
        }
        return new MondoRecord([]);
    }
    
}
