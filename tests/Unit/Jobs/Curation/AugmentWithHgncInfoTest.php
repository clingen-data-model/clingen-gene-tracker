<?php

namespace Tests\Unit\Jobs\Curation;

use App\Hgnc\HgncClientContract;
use App\Curation;
use App\Exceptions\ApiServerErrorException;
use App\Exceptions\HttpNotFoundException;
use App\ExpertPanel;
use App\Hgnc\HgncRecord;
use App\Jobs\Curations\AugmentWithHgncInfo;
use App\Notifications\Curations\GeneSymbolUpdated;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * @group hgnc
 */
class AugmentWithHgncInfoTest extends TestCase
{
    use DatabaseTransactions;

    public function setUp(): void
    {
        parent::setUp();
        $this->ep = factory(ExpertPanel::class)->create();
        $this->coord = factory(User::class)->create();
        $this->coord->expertPanels()->attach([$this->ep->id => [
            'is_coordinator' => 1,
        ]]);
        
        $this->curation = factory(Curation::class)->create([
            'gene_symbol' => 'TH',
            'expert_panel_id' => $this->ep->id,
        ]);

        $this->hgncClient = $this->getMockBuilder(HgncClientContract::class)
                                ->getMock();

        $this->hgncClient->method('fetchGeneSymbol')
                        ->willReturn(new HgncRecord([
                            'hgnc_id' => 'HGNC:11782',
                            'name' => 'tyrosine hydroxylase',
                            'symbol' => 'TH',
                            'prev_symbol' => 'THH',
                        ]));
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
    public function throws_ApiServerErrorException_if_api_returns_500()
    {
        $this->hgncClient->method('fetchGeneSymbol')
            ->will($this->throwException(new ApiServerErrorException('hgnc', 'http://rest.genenames.org/fetch/hgnc_id/2890')));

        $this->expectException(ApiServerErrorException::class);

        (new AugmentWithHgncInfo($this->curation))->handle($this->hgncClient);
    }

    /**
     * @test
     */
    public function adds_hgnc_name_hgnc_id_to_curation()
    {
        $job = new AugmentWithHgncInfo($this->curation);

        $job->handle($this->hgncClient);

        $this->assertDatabaseHas('curations', [
            'gene_symbol' => 'TH',
            'hgnc_name' => 'tyrosine hydroxylase',
            'hgnc_id' => '11782',
        ]);
    }

    /**
     * @test
     * @group notifications
     * @group mail
     */
    public function updates_previous_symbol_with_new_symbol_symbol_changed()
    {
        $this->curation->gene_symbol = 'MLTN1';
        $this->hgncClient->method('fetchGeneSymbol')
                        ->will($this->throwException(new HttpNotFoundException()));
        $this->hgncClient->method('fetchPreviousSymbol')
                        ->willReturn(new HgncRecord([
                            'hgnc_id' => 'HGNC:11782',
                            'symbol' => 'MLTN2',
                            'name' => 'Milton Dog',
                            'prev_symbol' => 'MLTN1',
                        ]));

        app()->instance(HgncClient::class, $this->hgncClient);

        Notification::fake();
        $job = new AugmentWithHgncInfo($this->curation);
        $job->handle($this->hgncClient);

        $this->assertDatabaseHas('curations', [
            'hgnc_id' => 11782,
            'gene_symbol' => 'MLTN2',
            'hgnc_name' => 'Milton Dog',
        ]);

        Notification::assertSentTo($this->coord, GeneSymbolUpdated::class);
    }
}
