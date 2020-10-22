<?php

namespace Tests\Unit;

use App\Exceptions\HttpNotFoundException;
use App\Exceptions\HttpUnexpectedResponseException;
use App\Hgnc\HgncClient;
use App\Hgnc\HgncRecord;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Tests\TestCase;

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
        $hgncClient = app()->make(\App\Hgnc\HgncClientContract::class);
        $this->assertInstanceOf(\App\Hgnc\HgncClient::class, $hgncClient);
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

    /**
     * @test
     * @group hgnc-custom-download
     */
    public function returns_collection_of_hgnc_records_for_custom_download()
    {
        $data = file_get_contents(base_path('tests/files/hgnc_api/custom_download.txt'));
        $client = $this->getClient([new Response(200, [], $data)]);
        $records = $client->fetchCustomDownload([
                    'col' => [
                        'gd_hgnc_id',
                        'gd_app_name',
                        'gd_status',
                        'gd_prev_sym',
                        'gd_aliases',
                        'gd_pub_acc_ids',
                        'gd_date_mod',
                        'md_mim_id',
                        'md_eg_id',
                    ],
                    'status' => [
                        'Approved',
                        'Entry Withdrawn',
                    ],
                    'hgnc_dbtag' => 'on',
                    'order_by' => 'gd_app_sym_sort',
                    'format' => 'text',
                    'submit' => 'submit',
                ]);

        $this->assertEquals(20, $records->count());
        $this->assertInstanceOf(HgncRecord::class, $records->first());
    }

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
