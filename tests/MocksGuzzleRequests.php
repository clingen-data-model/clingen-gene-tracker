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
    protected function getGuzzleClient($responses, $headers = [])
    {
        $mock = new MockHandler($responses);
        $stack = HandlerStack::create($mock);
        
        $mergedHeaders = array_merge(['Accept' => 'application/json'], $headers);

        $guzzleClient = new Client([
            'handler' => $stack,
            'headers' => $mergedHeaders,
        ]);

        return $guzzleClient;
    }
}
