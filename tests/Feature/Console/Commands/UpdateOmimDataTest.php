<?php

namespace Tests\Feature\Console\Commands;

use App\Gene;
use App\Phenotype;
use Tests\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Tests\MocksGuzzleRequests;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Handler\MockHandler;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\SeedsGenes;

/**
 * @group omim
 * @group phenotypes
 */
class UpdateOmimDataTest extends TestCase
{
    use DatabaseTransactions;
    use MocksGuzzleRequests;
    use SeedsGenes;

    public function setup():void
    {
        parent::setup();
        $testGeneMap = file_get_contents(base_path('tests/files/omim_api/genemap2.txt'));
        $httpClient = $this->getGuzzleClient([new Response(200, [], $testGeneMap)]);
        app()->instance(ClientInterface::class, $httpClient);

        $this->seedGenes();
    }
    
    /**
     * @test
     */
    public function downloads_omim_geneamp2_file_and_stores_phenotypes()
    {
        $this->artisan('omim:update-data');
        $this->assertEquals(19, Phenotype::count());
        $this->assertEquals(10, \DB::table('gene_phenotype')->groupBy()->get()->groupBy('hgnc_id')->count());
    }
}
