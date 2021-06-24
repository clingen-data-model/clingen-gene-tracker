<?php

namespace Tests\Feature\Console\Commands;

use App\Gene;
use App\AppState;
use App\Console\Commands\UpdateOmimData;
use App\Phenotype;
use Carbon\Carbon;
use Tests\TestCase;
use Tests\SeedsGenes;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Tests\MocksGuzzleRequests;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Handler\MockHandler;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

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
        $this->dateGenerated = Carbon::parse('2021-03-29');

        $this->seedGenes();
    }
    
    /**
     * @test
     */
    public function downloads_omim_geneamp2_file_and_stores_phenotypes()
    {
        $this->artisan('omim:update-data');
        $this->assertEquals(22, Phenotype::count());
        $this->assertEquals(11, \DB::table('gene_phenotype')->groupBy()->get()->groupBy('hgnc_id')->count());
    }

    /**
     * @test
     */
    public function adds_phenotype_moi_if_exists_on_row()
    {
        $this->artisan('omim:update-data');
        $this->assertDatabaseHas('phenotypes', [
            'mim_number' => 605429,
            'moi' => 'Autosomal dominant'
        ]);
    }
    

    /**
     * @test
     */
    public function processes_if_newer_than_last_download()
    {
        AppState::findByName('last_genemap_download')->update(['value'=>Carbon::parse('2021-03-28')]);
        $this->artisan('omim:update-data');
        $this->assertEquals(22, Phenotype::count());
        $this->assertEquals(11, \DB::table('gene_phenotype')->groupBy()->get()->groupBy('hgnc_id')->count());
    }

    /**
     * @test
     */
    public function sets_new_last_genemap_download_if_newer()
    {
        AppState::findByName('last_genemap_download')->update(['value'=>Carbon::parse('2021-03-28')]);
        $this->artisan('omim:update-data');

        $this->assertDatabaseHas('app_states', [
            'name' => 'last_genemap_download',
            'value' => '2021-03-29 00:00:00'
        ]);
    }
    
    /**
     * @test
     */
    public function does_not_process_if_not_newer_than_last_download()
    {
        AppState::findByName('last_genemap_download')->update(['value'=>Carbon::parse('2021-03-29')]);
        $this->artisan('omim:update-data');
        $this->assertEquals(0, Phenotype::count());
        $this->assertEquals(0, \DB::table('gene_phenotype')->groupBy()->get()->groupBy('hgnc_id')->count());
    }

    /**
     * @test
     */
    public function gets_gene_symbol_from_approved_symbol_or_approved_gene_symbol_index()
    {
        $command = new UpdateOmimData();

        $this->assertEquals('BOB', $this->invokeMethod($command, 'getGeneSymbol', [['approved_symbol' => 'BOB']]));
        $this->assertEquals('BOB', $this->invokeMethod($command, 'getGeneSymbol', [['approved_gene_symbol' => 'BOB']]));
    }
    
}
