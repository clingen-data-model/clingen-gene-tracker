<?php

namespace Tests;

use GuzzleHttp\Client;
use App\Clients\HgncClient;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\MockHandler;

trait HasHgncClient
{
    private function getClient($responses)
    {
        $mock = new MockHandler($responses);
        $stack = HandlerStack::create($mock);
        $guzzleClient = new Client([
            'handler' => $stack,
            'headers'=>[
                'Accept' => 'application/json'
            ]
        ]);
        return new HgncClient($guzzleClient);
    }
}
