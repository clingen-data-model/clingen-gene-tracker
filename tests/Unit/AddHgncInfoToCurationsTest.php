<?php

namespace Tests\Unit;

use App\Curation;
use Tests\TestCase;
use GuzzleHttp\Client;
use App\Clients\HgncClient;
use App\Console\Commands\AddHgncInfoToCurations;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Handler\MockHandler;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Contracts\HgncClient as HgncClientContract;
use Illuminate\Foundation\Testing\DatabaseTransactions;

/**
 * @group
 */
class AddHgncInfoToCurationsTest extends TestCase
{
    use DatabaseTransactions;

    public function setUp():void
    {
        parent::setUp();
        $c1 = factory(Curation::class)->create([
            'gene_symbol' => 'HT'
        ]);
        $c2 = factory(Curation::class)->create([
            'gene_symbol' => 'MLTN1'
        ]);
        $c3 = factory(Curation::class)->create([
            'gene_symbol' => 'BRCA1'
        ]);
    }

    /**
     * @test
     */
    public function adds_hgnc_name_and_id_to_all_existing_curations()
    {
        // $htResponse = file_get_contents(base_path('tests/files/hgnc_api/ht.json'));
        // $notFound = file_get_contents(base_path('tests/files/hgnc_api/numFound0.json'));

        // $hgncClient = $this->getClient([
        //     new Response(200, [], $htResponse),
        //     new Response(200, [], $notFound),
        //     new Response(200, [], $htResponse),
        // ]);
        // app()->instance(\HgncClientContract::class, $hgncClient);

        // $this->artisan('curations:add-hgnc-info');

        // $this->assertDatabaseHas('curations', [
        //     'gene_symbol' => 'HT',
        //     'hgnc_name' => 'tyrosine hydroxylase',
        //     'hgnc_id' => 11782

        // ]);
        
        // $this->assertDatabaseHas('curations', [
        //     'gene_symbol' => 'BRCA1',
        //     'hgnc_name' => 'tyrosine hydroxylase',
        //     'hgnc_id' => 11782
        // ]);

        // $this->assertDatabaseHas('curations', [
        //     'gene_symbol' => 'MLTN1',
        //     'hgnc_name' => null,
        //     'hgnc_id' => null
        // ]);
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
