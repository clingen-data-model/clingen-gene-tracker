<?php

namespace Tests\Unit\Jobs\Gci;

use App\Gene;
use Tests\TestCase;
use App\GciCuration;
use Ramsey\Uuid\Uuid;
use App\Gci\GciMessage;
use App\IncomingStreamMessage;
use Illuminate\Support\Facades\Bus;
use App\Jobs\Gci\UpdateGciCurationFromStreamMessage;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class UpdateGciCurationFromStreamMessageTest extends TestCase
{
    use DatabaseTransactions;


    public function setup():void
    {
        parent::setup();
        $this->uuid = Uuid::uuid4();
        $this->gciCuration = factory(GciCuration::class)->create([
            'gdm_uuid' => $this->uuid->toString(),
            'mondo_id' => 'MONDO:109999',
            'moi_id' => 10,
            'status_id' => 9,
            'affiliation_id' => 13,
            'classification_id' => 1
        ]);
    }

    /**
     * @test
     */
    public function updates_existing_gci_curation()
    {
        $ism = $this->setupIsm();
        $gciMessage = new GciMessage($ism->payload);
        Bus::dispatch(new UpdateGciCurationFromStreamMessage($gciMessage));

        $this->assertDatabaseHas('gci_curations', [
            'gdm_uuid' => $this->uuid,
            'classification_id' => 2,
            'status_id' => 6
        ]);
    }

    /**
     * @test
     */
    public function creates_new_gci_curation_if_status_is_created()
    {
        $this->gciCuration->forceDelete();
        $ism = $this->setupIsm();
        $payload = $ism->payload;
        $payload->status = 'created';
        $ism->payload = $payload;
        $gciMessage = new GciMessage($ism->payload);
        Bus::dispatch(new UpdateGciCurationFromStreamMessage($gciMessage));

        $this->assertDatabaseHas('gci_curations', [
            'gdm_uuid' => $this->uuid,
            'classification_id' => 2,
            'status_id' => 4
        ]);
    }

    /**
     * @test
     */
    public function creates_new_gci_curation_if_update_and_no_existing_record()
    {
        $this->gciCuration->forceDelete();
        $ism = $this->setupIsm();
        $gciMessage = new GciMessage($ism->payload);
        Bus::dispatch(new UpdateGciCurationFromStreamMessage($gciMessage));

        $this->assertDatabaseHas('gci_curations', [
            'gdm_uuid' => $this->uuid,
            'classification_id' => 2,
            'status_id' => 6
        ]);
    }
    

    public function setupIsm()
    {
        $ism = factory(IncomingStreamMessage::class)->make([
            'gdm_uuid' => $this->uuid->toString(),
        ]);
        $ism->payload = (object)array_merge((array)$ism->payload, [
            'report_id' => $this->uuid,
            'performed_by' => (object)[
                'on_behalf_of' => (object)[
                    'id' => 40007,
                    'name' => 'bob'
                ]
            ],
            'gene_validity_evidence_level' => (object) [
                'evidence_level' => 'Strong',
                'genetic_condition' => [
                    'gene' => 'hgnc:1',
                    'condition' => "MONDO:109999",
                    'mode_of_inheritance' => 'HP:0000007'
                ]
            ],
            'status' => (object)['name' => 'approved', 'date'=>'2021-06-08T16:21:21.426Z']
        ]);
        return $ism;
    }
}
