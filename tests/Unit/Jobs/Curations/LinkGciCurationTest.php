<?php

namespace Tests\Unit\Jobs\Curations;

use App\Gene;
use App\Curation;
use Carbon\Carbon;
use Tests\TestCase;
use App\ExpertPanel;
use App\GciCuration;
use Ramsey\Uuid\Uuid;
use App\IncomingStreamMessage;
use Illuminate\Support\Facades\Bus;
use App\Jobs\Curations\LinkGciCuration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class LinkGciCurationTest extends TestCase
{
    use DatabaseTransactions;

    public function setup():void
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

        $this->gciCuration = factory(GciCuration::class)->create([
            'status_id' => 9,
            'classification_id' => 1,
            'gdm_uuid' => $this->uuid->toString(),
            'hgnc_id' => $this->gene->hgnc_id,
            'mondo_id' => 'MONDO:0044312',
            'moi_id' => 2,
            'affiliation_id' => $this->expertPanel->id
        ]);
    }

    /**
     * @test
     */
    public function links_GciCuration_to_Curation_if_gene_condition_and_moi_match()
    {
        Bus::dispatch(new LinkGciCuration($this->curation));
        
        $this->assertDatabaseHas('curations', [
            'id' => $this->curation->id,
            'gdm_uuid' => $this->uuid->toString()
        ]);
    }

    /**
     * @test
     */
    public function updates_curation_based_on_stream_messages()
    {
        $ism = factory(IncomingStreamMessage::class)->create([
            'gdm_uuid' => $this->uuid->toString(),
            'payload' => [
                'date' => Carbon::now()->toIsoString(),
                'status' => [
                    'name' => 'published',
                    'date' => Carbon::parse('2010-01-01')->toIsoString(),
                ],
                'report_id' => $this->uuid->toString(),
                "contributors" => [
                    [
                        "id" => Uuid::uuid4(),
                        "name" => 'Bob butterfield',
                        "email" => 'bob@genes.com',
                        "roles" => [
                            "creator"
                        ]
                    ]
                ],
                "performed_by" => [
                    "id" => Uuid::uuid4(),
                    "name" => 'bob',
                    "email" => 'buttefield',
                    "on_behalf_of" => [
                        "id" => $this->expertPanel->affiliation->parent->clingen_id,
                        "name" => $this->expertPanel->affiliation->parent->name
                    ]
                ],
                "gene_validity_evidence_level" => [
                    "evidence_level" => "Definitive",
                    "gene_validity_sop" => "",
                    "genetic_condition" => [
                        "gene" => 'HGNC:'.$this->gene->hgnc_id,
                        "condition" => "MONDO:0044312",
                        "mode_of_inheritance" => 'HP:0000006'
                    ]
                ]
            ]
        ]);

        Bus::dispatch(new LinkGciCuration($this->curation));
        
        $this->assertDatabaseHas('curation_curation_status', [
            'curation_id' => $this->curation->id,
            'curation_status_id' => config('curations.statuses.published'),
        ]);
        $this->assertDatabaseHas('classification_curation', [
            'curation_id' => $this->curation->id,
            'classification_id' => config('curations.classifications.definitive'),
        ]);
    }
}
