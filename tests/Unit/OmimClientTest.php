<?php

namespace Tests\Unit;

use App\Clients\OmimClient;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

/**
 * @group omim-client
 * @group omim
 */
class OmimClientTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
    }

    /**
     * @test
     */
    public function can_get_an_entry()
    {
        $entryJson = file_get_contents(base_path('tests/files/omim_api/entry_response.json'));
        $omim = $this->getOmimClient([ new Response(200, [], $entryJson) ]);
        $entry = $omim->getEntry(100100);

        $expectedEntry = json_decode($entryJson)->omim->entryList;

        $this->assertEquals($expectedEntry, $entry);
    }

    /**
     * @test
     */
    public function can_search_omim_database()
    {
        $json = file_get_contents(base_path('tests/files/omim_api/search_response.json'));
        $omim = $this->getOmimClient([new Response(200, [], $json)]);
        $results = $omim->search(['search'=>'myl2']);

        $expectedEntries = collect(json_decode($json)->omim->searchResponse->entryList)->transform(function ($entry) {
            return $entry->entry;
        });
        ;
        $this->assertEquals($expectedEntries, $results);
    }

    /**
     * @test
     */
    public function can_get_phenotypes_for_hgnc_gene_symbol()
    {
        $json = file_get_contents(base_path('tests/files/omim_api/gene_phenotypes_search.json'));
        $omim = $this->getOmimClient([new Response(200, [], $json)]);
        $results = $omim->getGenePhenotypes('TP53');

        $expectedEntries = collect(json_decode($json)->omim->searchResponse->entryList[0]->entry->geneMap->phenotypeMapList)
                                ->transform(function ($item) {
                                    return $item->phenotypeMap;
                                });
        $this->assertEquals($expectedEntries, $results);
    }
    

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
