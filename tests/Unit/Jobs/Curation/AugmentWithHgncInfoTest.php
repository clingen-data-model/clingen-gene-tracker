<?php

namespace Tests\Unit\Job\Curation;

use App\User;
use App\Curation;
use Tests\TestCase;
use App\ExpertPanel;
use OutOfBoundsException;
use App\Contracts\HgncClient;
use App\Exceptions\HttpNotFoundException;
use App\Jobs\Curation\AugmentWithHgncInfo;
use App\Mail\Curations\GeneSymbolUpdated;
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
        $this->ep = factory(ExpertPanel::class)->create();
        $this->coord = factory(User::class)->create();
        $this->coord->expertPanels()->attach([$this->ep->id => [
            'is_coordinator' => 1
        ]]);
        $this->curation = factory(Curation::class)->create([
            'gene_symbol' => 'TH',
            'expert_panel_id' => $this->ep->id
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
        $this->hgncClient->method('fetchPreviousSymbol')
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
    
    /**
     * @test
     */
    public function updates_previous_symbol_with_new_symbol_symbol_changed()
    {
        $this->curation->gene_symbol = 'MLTN1';
        $this->hgncClient->method('fetchGeneSymbol')
                        ->will($this->throwException(new HttpNotFoundException()));
        $this->hgncClient->method('fetchPreviousSymbol')
                        ->willReturn((object)[
                            'hgnc_id' => 11782,
                            'symbol' => 'MLTN2',
                            'name' => 'Milton Dog',
                            'prev_symbol' => 'MLTN1'
                        ]);

        app()->instance(HgncClient::class, $this->hgncClient);

        \Mail::fake();
        $job = new AugmentWithHgncInfo($this->curation);
        $job->handle($this->hgncClient);

        $this->assertDatabaseHas('curations', [
            'hgnc_id' => 11782,
            'gene_symbol' => 'MLTN2',
            'hgnc_name' => 'Milton Dog'
        ]);

        \Mail::assertSent(GeneSymbolUpdated::class, function ($mail) {
            return $mail->hasTo($this->coord->email);
        });
    }
}
