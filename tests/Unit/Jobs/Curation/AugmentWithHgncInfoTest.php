<?php

namespace Tests\Unit\Job\Curation;

use App\Curation;
use Tests\TestCase;
use OutOfBoundsException;
use App\Contracts\HgncClient;
use App\Exceptions\HttpNotFoundException;
use App\Jobs\Curation\AugmentWithHgncInfo;
use Illuminate\Foundation\Testing\DatabaseTransactions;

/**
 * @group hgnc
 */
class AugmentWithHgncInfoTest extends TestCase
{
    use DatabaseTransactions;

    public function setUp():void
    {
        parent::setUp();
        $this->curation = factory(Curation::class)->create([
            'gene_symbol' => 'TH'
        ]);
        $this->hgncClient = $this->getMockBuilder(HgncClient::class)
                                ->getMock();
        $this->hgncClient->method('fetchGeneSymbol')
                        ->willReturn((object)[
                            'hgnc_id' => 11782,
                            'name' => 'tyrosine hydroxylase'
                        ]);
    }


    /**
     * @test
     */
    public function throws_exception_if_gene_symbol_not_found()
    {
        $this->curation->gene_symbol = 'MLTN1';
        $this->hgncClient->method('fetchGeneSymbol')
                        ->will($this->throwException(new HttpNotFoundException()));

        app()->instance(HgncClient::class, $this->hgncClient);

        $job = new AugmentWithHgncInfo($this->curation);

        $this->expectException(HttpNotFoundException::class);
        $job->handle($this->hgncClient);
    }
    


    /**
     * @test
     */
    public function adds_hgnc_name_hgnc_id_to_curation()
    {
        $job = new AugmentWithHgncInfo($this->curation);

        $job->handle($this->hgncClient);

        $this->assertDatabaseHas('curations', ['gene_symbol' => 'TH', 'hgnc_name' => 'tyrosine hydroxylase', 'hgnc_id' => 11782]);
    }
    
}
