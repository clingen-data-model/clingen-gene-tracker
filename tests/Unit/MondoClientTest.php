<?php

namespace Tests\Unit;

use Tests\TestCase;
use GuzzleHttp\Client;
use App\Clients\MondoClient;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Handler\MockHandler;
use App\Exceptions\HttpNotFoundException;
use Illuminate\Foundation\Testing\WithFaker;
use App\Contracts\MondoClient as MondoClientContract;

/**
 * @group mondo
 * @group clients
 */
class MondoClientTest extends TestCase
{

    /**
     * @test
     */
    public function app_resolves_abstract_MondoClient_with_Clients_MondoClient()
    {
        $client = app()->make(MondoClientContract::class);
        $this->assertInstanceOf(MondoClient::class, $client);
    }


    /**
     * @test
     */
    public function throws_new_HttpNotFoundException_when_no_results_for_mondo_id()
    {
        $mondoClient = $this->getClient([new Response(404, [])]);

        $this->expectException(HttpNotFoundException::class);
        $record = $mondoClient->fetchRecord(1298371283);
    }
    

    /**
     * @test
     */
    public function fetches_mondo_record_by_symbol_by_gene_symbol()
    {
        $json = file_get_contents(base_path('tests/files/mondo_api/arteritis.json'));
        $mondoClient = $this->getClient([new Response(200, [], $json)]);
        $record = $mondoClient->fetchRecord('0043494');

        $expectedRecord = json_decode($json);
        
        $this->assertEquals($expectedRecord, $record->getAttributes());
    }
    

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
        return new MondoClient($guzzleClient);
    }
}
