<?php

namespace Tests\Unit;

use Tests\TestCase;
use GuzzleHttp\Client;
use App\Clients\HgncClient;
use App\Contracts\GeneSymbolUpdate;
use App\Contracts\GeneSymbolUpdateContract;
use App\Exceptions\HttpNotFoundException;
use App\Exceptions\HttpUnexpectedResponseException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Handler\MockHandler;

/**
 * @group hgnc
 * @group clients
 */
class HgncClientTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * @test
     */
    public function app_resolves_abstract_HgncClient_w_concrete_implementation()
    {
        $hgncClient = app()->make(\App\Contracts\HgncClient::class);
        $this->assertInstanceOf(\App\Clients\HgncClient::class, $hgncClient);
    }
    

    /**
     * @test
     */
    public function throws_new_HttpNotFoundException_when_no_results_for_gene_symbol()
    {
        $json = file_get_contents(base_path('tests/files/hgnc_api/numFound0.json'));
        $hgncClient = $this->getClient([new Response(200, [], $json)]);

        $this->expectException(HttpNotFoundException::class);
        $record = $hgncClient->fetchGeneSymbol('HT');
    }
    

    /**
     * @test
     */
    public function fetches_hgnc_record_by_symbol_by_gene_symbol()
    {
        $json = file_get_contents(base_path('tests/files/hgnc_api/ht.json'));
        $hgncClient = $this->getClient([new Response(200, [], $json)]);
        $record = $hgncClient->fetchGeneSymbol('HT');

        $expectedRecord = json_decode($json)->response->docs[0];
        
        $this->assertEquals($expectedRecord, $record->getAttributes());
    }

    /**
     * @test
     */
    public function throws_HttpUnexpectedResponseException_when_more_than_one_record_found_for_gene_symbol()
    {
        $json = file_get_contents(base_path('tests/files/hgnc_api/multiple_found.json'));
        $hgncClient = $this->getClient([new Response(200, [], $json)]);

        $this->expectException(HttpUnexpectedResponseException::class);

        $hgncClient->fetchGeneSymbol('MLTIRECORD1');
    }
    
    /**
     * @test
     */
    public function fetches_record_by_hgnc_id()
    {
        $json = file_get_contents(base_path('tests/files/hgnc_api/ht.json'));
        $hgncClient = $this->getClient([new Response(200, [], $json)]);

        $record = $hgncClient->fetchHgncId('123');

        $expectedRecord = json_decode($json)->response->docs[0];
        
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
        return new HgncClient($guzzleClient);
    }
}
