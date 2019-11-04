<?php

namespace Tests\Traits;

use GuzzleHttp\Client;
use App\Clients\OmimClient;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\MockHandler;

/**
 * Includes private method getOmimClient
 */
trait GetsOmimClient
{
    private function getOmimClient($responses)
    {
        $mock = new MockHandler($responses);
        $stack = HandlerStack::create($mock);
        $guzzleClient = new Client([
            'handler' => $stack,
            'headers'=>[
                'ApiKey' => config('app.omim_key')
            ]
        ]);
        return new OmimClient($guzzleClient);
    }

}
