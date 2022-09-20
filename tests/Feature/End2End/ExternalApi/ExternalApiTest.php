<?php

namespace Tests\Feature\End2End\ExternalApi;

use App\ApiClient;
use Tests\TestCase;
use Illuminate\Testing\TestResponse;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;

abstract class ExternalApiTest extends TestCase
{
    use DatabaseTransactions;
    use WithFaker;

    protected ApiClient $client;
    protected string $token;

    public function setup():void
    {
        parent::setup();
        $this->client = ApiClient::factory()->create();
        $this->token = $this->client->createToken('test')->plainTextToken;
    }

    public function makeExternalApiRequest(
        string $method, 
        string $uri, 
        array $data = [], 
        array $headers = []
    ): TestResponse
    {
        $headers = array_merge($headers, ['Authorization' => 'Bearer '.$this->token]);
        
        return $this->json($method, $uri, $data, $headers);
    }

    public function makeExternalApiRequestAsGuest(
        string $method, 
        string $uri, 
        array $data = [], 
        array $headers = []
    ): TestResponse
    {
        return $this->json($method, $uri, $data, $headers);
    }

}