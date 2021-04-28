<?php

namespace Tests\Traits;

use GuzzleHttp\Client;
use App\Clients\OmimClient;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\MockHandler;
use Tests\MocksGuzzleRequests;

/**
 * Includes private method getOmimClient
 */
trait GetsOmimClient
{
    use MocksGuzzleRequests;

    private function getOmimClient($responses)
    {
        $guzzleClient = $this->getGuzzleClient($responses, ['ApiKey' => config('app.omim_key')]);
        return new OmimClient($guzzleClient);
    }
}
