<?php

namespace Tests\Unit\Hgnc;

use App\Hgnc\CustomDownloadImporter;
use App\Hgnc\HgncClient;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * @group hgnc-custom-download
 */
class CustomDownloadImporterTest extends TestCase
{
    // use DatabaseTransactions;

    public function setup(): void
    {
        parent::setup();
    }

    /**
     * @test
     */
    public function creates_gene_record_if_hgnc_not_found()
    {
        $data = file_get_contents(base_path('tests/files/hgnc_api/custom_download.txt'));
        $importer = $this->getImporter([new Response(200, [], $data)]);

        $importer->import();

        $this->assertEquals(20, DB::table('genes')->count());

        $this->assertDatabaseHas('genes', [
            'hgnc_id' => 5,
            'gene_symbol' => 'A1BG',
            'hgnc_name' => 'alpha-1-B glycoprotein',
            'hgnc_status' => 'Approved',
            'previous_symbols' => null,
            'alias_symbols' => null,
            'ncbi_gene_id' => 1,
            'omim_id' => 138670,
        ]);

        $this->assertDatabaseHas('genes', [
            'gene_symbol' => 'A1BG-AS1',
            'hgnc_id' => 37133,
            'omim_id' => null,
            'ncbi_gene_id' => 503538,
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
            'ncbi_gene_id' => 343066,
            'omim_id' => null,
        ]);
    }

    private function getImporter($responses)
    {
        $mock = new MockHandler($responses);

        $stack = HandlerStack::create($mock);
        $guzzleClient = new Client([
            'handler' => $stack,
        ]);

        return new CustomDownloadImporter(new HgncClient($guzzleClient));
    }
}
