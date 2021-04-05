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

/**
 * @group omim
 * @group phenotypes
 */
class UpdateOmimDataTest extends TestCase
{
    use DatabaseTransactions;
    use MocksGuzzleRequests;

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
    
    private function seedGenes()
    {
        $lines = [
            [
                'hgnc_id' => 94,
                'gene_symbol' => 'ACAT2',
                'omim_id' => '100678',
                'hgnc_name' => 'acetyl-CoA acetyltransferase 2',
                'hgnc_status' => 'Approved'
            ],
            [
                'hgnc_id' => 1552,
                'gene_symbol' => 'CBX2',
                'omim_id' => '602770',
                'hgnc_name' => 'chromobox 2',
                'hgnc_status' => 'Approved'
            ],
            [
                'hgnc_id' => 4220,
                'gene_symbol' => 'GDF5',
                'omim_id' => '601146',
                'hgnc_name' => 'growth differentiation factor 5',
                'hgnc_status' => 'Approved'
            ],
            [
                'hgnc_id' => 8846,
                'gene_symbol' => 'PER2',
                'omim_id' => '603426',
                'hgnc_name' => 'period circadian regulator 2',
                'hgnc_status' => 'Approved'
            ],
            [
                'hgnc_id' => 8847,
                'gene_symbol' => 'PER3',
                'omim_id' => '603427',
                'hgnc_name' => 'period circadian regulator 3',
                'hgnc_status' => 'Approved'
            ],
            [
                'hgnc_id' => 11600,
                'gene_symbol' => 'TBX22',
                'omim_id' => '300307',
                'hgnc_name' => 'T-box transcription factor 22',
                'hgnc_status' => 'Approved'
            ],
            [
                'hgnc_id' => 14211,
                'gene_symbol' => 'BLNK',
                'omim_id' => '604515',
                'hgnc_name' => 'B cell linker',
                'hgnc_status' => 'Approved'
            ],
            [
                'hgnc_id' => 24248,
                'gene_symbol' => 'EEF1AKNMT',
                'omim_id' => '617987',
                'hgnc_name' => 'eEF1A lysine and N-terminal methyltransferase',
                'hgnc_status' => 'Approved'
            ],
            [
                'hgnc_id' => 29796,
                'gene_symbol' => 'LAMTOR2',
                'omim_id' => '610389',
                'hgnc_name' => 'late endosomal/lysosomal adaptor, MAPK and MTOR activator 2',
                'hgnc_status' => 'Approved'
            ],
            [
                'hgnc_id' => 30477,
                'gene_symbol' => 'HEPHL1',
                'omim_id' => '618455',
                'hgnc_name' => 'hephaestin like 1',
                'hgnc_status' => 'Approved'
            ],
        ];

        foreach ($lines as $geneData) {
            Gene::create($geneData);
        }
    }
    
}

