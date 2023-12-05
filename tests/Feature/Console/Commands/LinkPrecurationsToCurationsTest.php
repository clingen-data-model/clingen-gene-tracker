<?php

namespace Tests\Feature\Console\Commands;

use App\Curation;
use App\ExpertPanel;
use App\Gene;
use App\Jobs\Curations\LinkGciCuration;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

/**
 * @group gci
 * @group curations
 */
class LinkPrecurationsToCurationsTest extends TestCase
{
    use DatabaseTransactions;

    public function setup(): void
    {
        parent::setup();

        $this->gene = factory(Gene::class)->create();

        $this->uuid = Uuid::uuid4();
        $this->expertPanel = ExpertPanel::find(5);

        $this->curation = factory(Curation::class)->create([
            'gdm_uuid' => null,
            'hgnc_id' => $this->gene->hgnc_id,
            'mondo_id' => 'MONDO:0044312',
            'moi_id' => 2,
            'expert_panel_id' => $this->expertPanel->id,
        ]);
        $curationWGdmUuid = factory(Curation::class)->create();
    }

    /**
     * @test
     */
    public function links_GciCuration_to_Curation_if_gene_condition_and_moi_match(): void
    {
        Bus::fake();
        Artisan::call('curations:link-gci');

        Bus::assertDispatchedTimes(LinkGciCuration::class, 1);
    }
}
