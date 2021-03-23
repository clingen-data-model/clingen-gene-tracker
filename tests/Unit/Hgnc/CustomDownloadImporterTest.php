<?php

namespace Tests\Unit\Hgnc;

use App\Gene;
use Tests\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Tests\MocksGuzzleRequests;
use Illuminate\Support\Facades\DB;
use GuzzleHttp\Handler\MockHandler;
use App\Hgnc\CustomDownloadImporter;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;

/**
 * @group hgnc-custom-download
 * @group hgnc
 */
class CustomDownloadImporterTest extends TestCase
{
    use MocksGuzzleRequests;
    use DatabaseTransactions;

    public function setup(): void
    {
        parent::setup();
        DB::table('genes')->delete();
    }

    /**
     * @test
     */
    public function creates_gene_record_if_hgnc_not_found()
    {
        $data = file_get_contents(base_path('tests/files/hgnc_api/custom_download.txt'));
        $importer = $this->getImporter([new Response(200, [], $data)]);

        foreach($importer->import() as $msg) {

        }

        $this->assertEquals(20, DB::table('genes')->count());

        $this->assertDatabaseHas('genes', [
            'hgnc_id' => 5,
            'gene_symbol' => 'A1BG',
            'hgnc_name' => 'alpha-1-B glycoprotein',
            'hgnc_status' => 'Approved',
            'previous_symbols' => null,
            'alias_symbols' => null,
            'omim_id' => 138670,
        ]);

        $this->assertDatabaseHas('genes', [
            'gene_symbol' => 'A1BG-AS1',
            'hgnc_id' => 37133,
            'omim_id' => null,
            'hgnc_name' => 'A1BG antisense RNA 1',
            'hgnc_status' => 'Approved',
            'previous_symbols' => $this->castToJson(['NCRNA00181', 'A1BGAS', 'A1BG-AS']),
            'alias_symbols' => $this->castToJson(['FLJ23569']),
        ]);

        $this->assertDatabaseHas('genes', [
            'hgnc_id' => 32038,
            'gene_symbol' => 'AADACL4',
            'hgnc_name' => 'arylacetamide deacetylase like 4',
            'hgnc_status' => 'Approved',
            'alias_symbols' => $this->castToJson(['OTTHUMG00000001889']),
            'previous_symbols' => null,
            'omim_id' => null,
        ]);
    }

    /**
     * @test
     */
    public function updates_gene_if_hgnc_id_found()
    {
        Carbon::setTestNow('2020-01-01');
        $gene = Gene::create([
            'hgnc_id' => 5,
            'gene_symbol' => 'A1BG',
            'hgnc_name' => 'geekoprotien',
            'hgnc_status' => 'Approved',
            'previous_symbols' => null,
            'alias_symbols' => null,
            'ncbi_gene_id' => 1,
            'omim_id' => 138670,
        ]);

        $data = file_get_contents(base_path('tests/files/hgnc_api/custom_download.txt'));
        $importer = $this->getImporter([new Response(200, [], $data)]);

        foreach($importer->import() as $a){}

        $this->assertEquals(20, DB::table('genes')->count());
        $this->assertDatabaseHas('genes', [
            'hgnc_id' => 5,
            'gene_symbol' => 'A1BG',
            'hgnc_name' => 'alpha-1-B glycoprotein',
            'updated_at' => Carbon::now()
        ]);
    }
    

    private function getImporter($responses)
    {
        $guzzleClient = $this->getGuzzleClient($responses);

        return new CustomDownloadImporter($guzzleClient);
    }
}
