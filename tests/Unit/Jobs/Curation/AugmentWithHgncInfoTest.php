<?php

namespace Tests\Unit\Jobs\Curation;

use App\Hgnc\HgncClientContract;
use App\Curation;
use App\Exceptions\ApiServerErrorException;
use App\Exceptions\HttpNotFoundException;
use App\ExpertPanel;
use App\Gene;
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
    }

    // /**
    //  * @test
    //  */
    // public function throws_exception_if_gene_symbol_not_found()
    // {
    //     $this->curation->gene_symbol = 'MLTN1';

    //     $job = new AugmentWithHgncInfo($this->curation);

    //     $this->expectException(HttpNotFoundException::class);
    //     $job->handle();
    // }

    /**
     * @test
     */
    public function adds_hgnc_name_hgnc_id_to_curation()
    {
        $gene = factory(Gene::class)->create([
            'gene_symbol' => 'TH',
            'hgnc_name' => 'tyrosine hydroxylase',
            'hgnc_id' => 11782
        ]);
        $job = new AugmentWithHgncInfo($this->curation);
        $job->handle();

        $this->assertEquals($gene->hgnc_name, $this->curation->hgnc_name);
        $this->assertEquals($gene->hgnc_id, $this->curation->hgnc_id);
    }

    /**
     * @test
     * @group notifications
     * @group mail
     */
    public function updates_previous_symbol_with_new_symbol_symbol_changed()
    {
        $gene = factory(Gene::class)->create([
            'hgnc_id' => 11782,
            'gene_symbol' => 'MLTN2',
            'hgnc_name' => 'Milton Dog',
            'previous_symbols' => ["MLTN1"],
        ]);

        $this->curation->gene_symbol = 'MLTN1';

        Notification::fake();
        $job = new AugmentWithHgncInfo($this->curation);
        $job->handle();

        $this->assertEquals($gene->hgnc_name, $this->curation->hgnc_name);
        $this->assertEquals($gene->hgnc_id, $this->curation->hgnc_id);

        Notification::assertSentTo($this->coord, GeneSymbolUpdated::class);
    }
}
