<?php

namespace Tests;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\MockHandler;

/**
 *
 */
trait MocksGuzzleRequests
{
    protected function getGuzzleClient($responses)
    {
        $mock = new MockHandler($responses);
        $stack = HandlerStack::create($mock);
        $guzzleClient = new Client([
            'handler' => $stack,
            'headers' => [
                'Accept' => 'application/json',
            ],
        ]);

        return $guzzleClient;
    }
}
