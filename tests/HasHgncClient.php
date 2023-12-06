<?php

namespace Tests;

use App\Hgnc\HgncClient;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;

trait HasHgncClient
{
    private function getClient($responses)
    {
        $mock = new MockHandler($responses);
        $stack = HandlerStack::create($mock);
        $guzzleClient = new Client([
            'handler' => $stack,
            'headers' => [
                'Accept' => 'application/json',
            ],
        ]);

        return new HgncClient($guzzleClient);
    }
}
