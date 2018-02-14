<?php

namespace Tests\Unit;

use App\Clients\OmimClient;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

/**
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
        $entryJson = '{"omim": { "version": "1.0","entryList":[{"entry":{"prefix":"#","mimNumber":100100,"status":"live","titles":{"preferredTitle":"PRUNE BELLY SYNDROME; PBS","alternativeTitles": "ABDOMINAL MUSCLES, ABSENCE OF, WITH URINARY TRACT ABNORMALITY AND CRYPTORCHIDISM;;\nEAGLE-BARRETT SYNDROME; EGBRS"}}}]}}';
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

        $expectedEntries = json_decode($json)->omim->searchResponse->entryList;
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
