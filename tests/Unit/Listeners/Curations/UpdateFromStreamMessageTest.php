<?php

namespace Tests\Unit\Listeners\Curations;

use App\Gene;
use App\Curation;
use Carbon\Carbon;
use Tests\TestCase;
use App\Affiliation;
use App\ExpertPanel;
use App\StreamError;
use Ramsey\Uuid\Uuid;
use App\CurationStatus;
use App\ModeOfInheritance;
use App\Jobs\Curations\AddStatus;
use App\DataExchange\Events\Received;
use App\Disease;
use App\Jobs\Curations\SetOwner;
use Illuminate\Foundation\Testing\DatabaseTransactions;

/**
 * @group gci
 * @group curations
 */
class UpdateFromStreamMessageTest extends TestCase
{
    use DatabaseTransactions;

    public function setup(): void
    {
        parent::setup();
        $this->createMsgPath = base_path('tests/files/gci_messages/created_message.json');
        $this->provisionalMsgPath = base_path('tests/files/gci_messages/provisionally_approved.json');
        $this->approvedMsgPath = base_path('tests/files/gci_messages/approved.json');
        $this->approvedWithStatusDateMsgPath = base_path('tests/files/gci_messages/approved_with_status_date.json');
        $this->gdmTransfered = base_path('tests/files/gci_messages/gdm_transfered.json');
        $this->diseaseChanged = base_path('tests/files/gci_messages/disease_change.json');

        factory(Gene::class)->create([
            'gene_symbol' => 'DICER1',
            'hgnc_id' => 17098,
        ]);

    }

    /**
     * @test
     */
    public function updates_curation_with_gdm_uuid_if_found()
    {
        $gdmTrio = [
            'hgnc_id' => 17098,
            'mondo_id' => 'MONDO:0011111',
            'moi_id' => 2
        ];
        
        $curationOther = factory(Curation::class)
                            ->create(
                                array_merge(['uuid' => '0c861e10-78a7-4ebc-ac57-853fb16f94c9', 'gene_symbol' => 'FRTS'], $gdmTrio)
                            );
        
        $curationOther2 = factory(Curation::class)
                            ->create(
                                array_merge(['gene_symbol' => 'FRTY'], $gdmTrio)
                            );

        $gdmTrio['gdm_uuid'] = '0c861e10-78a7-4ebc-ac57-853fb16f94c9';

        $curationTarget = factory(Curation::class)->create($gdmTrio);
        $payload = $this->fireTestEvent($this->approvedWithStatusDateMsgPath);

        $this->assertDatabaseHas('curations', [
            'id' => $curationOther->id,
            'gdm_uuid' => null
        ]);

        $this->assertDatabaseHas('curations', [
            'id' => $curationOther2->id,
            'gdm_uuid' => null
        ]);

        $this->assertDatabaseHas('curations', [
            'id' => $curationTarget->id,
            'gdm_uuid' => $gdmTrio['gdm_uuid']
        ]);
    }

    /**
     * @test
     */
    public function adds_to_missing_list_if_curation_cannot_be_matched()
    {
        $kafkaMessage = $this->makeMessage(file_get_contents($this->createMsgPath));
        $event = new Received($kafkaMessage);

        event($event);

        $this->assertDatabaseHas('stream_errors', [
            'type' => 'unmatchable curation',
            'direction' => 'incoming',
            // 'message_payload' => json_encode(json_decode($kafkaMessage->payload)),
            'notification_sent_at' => null,
        ]);

        $streamError = StreamError::query()->first();
        $this->assertEquals($streamError->message_payload->report_id, json_decode($kafkaMessage->payload)->report_id);
    }

    /**
     * @test
     */
    public function updates_gdm_uuid_moi_and_affiliation_if_create_message()
    {
        $curation = $this->createDICER1();

        $payload = json_decode(file_get_contents($this->createMsgPath));
        $kafkaMessage = $this->makeMessage(json_encode($payload));
        $event = new Received($kafkaMessage);

        event($event);

        $this->assertDatabaseHas('curations', [
            'hgnc_id' => 17098,
            'mondo_id' => 'MONDO:0011111',
            'affiliation_id' => Affiliation::findByClingenId($payload->performed_by->on_behalf_of->id)->id,
            'gdm_uuid' => $payload->report_id,
            'moi_id' => ModeOfInheritance::findByHpId($payload->gene_validity_evidence_level->genetic_condition->mode_of_inheritance)->id,
        ]);
    }

    /**
     * @test
     */
    public function updates_uuid_moi_affiliation_on_update()
    {
        $curation = $this->createDICER1();

        $payload = json_decode(file_get_contents($this->approvedMsgPath));
        $kafkaMessage = $this->makeMessage(json_encode($payload));
        $event = new Received($kafkaMessage);

        event($event);

        $this->assertDatabaseHas('curations', [
            'id' => $curation->id,
            'affiliation_id' => Affiliation::findByClingenId($payload->performed_by->on_behalf_of->id)->id,
            'moi_id' => ModeOfInheritance::findByHpId($payload->gene_validity_evidence_level->genetic_condition->mode_of_inheritance)->id,
        ]);
    }

    /**
     * If there's no gdm_uuid on the curation the event should
     * not be matchable if the hgnc_id and mondo_id do not match.
     * If not matched it can't be updated.
     *
     * @test
     */
    public function does_not_update_mondo_id_when_curation_not_linked_to_gdm()
    {
        $curation = $this->createDICER1();

        $payload = json_decode(file_get_contents($this->approvedMsgPath));
        $payload->gene_validity_evidence_level->genetic_condition->condition = 'MONDO:8888888';
        $kafkaMessage = $this->makeMessage(json_encode($payload));
        $event = new Received($kafkaMessage);

        event($event);

        $this->assertDatabaseHas('curations', [
            'hgnc_id' => 17098,
            'mondo_id' => 'MONDO:0011111',
        ]);
    }

    /**
     * @test
     *
     * Assume a change in mondo id if curation has gdm_uuid
     * and event with gdm_uuid has different mondo than curation.
     *
     */
    public function updates_mondo_id_on_update_when_matched_by_gdm_uuid()
    {
        $curation = $this->createDICER1();

        $payload = json_decode(file_get_contents($this->approvedMsgPath));
        $payload->gene_validity_evidence_level->genetic_condition->condition = 'MONDO:8888888';
        
        $curation->update(['gdm_uuid' => $payload->report_id]);
        
        $kafkaMessage = $this->makeMessage(json_encode($payload));
        $event = new Received($kafkaMessage);

        event($event);

        $this->assertDatabaseHas('curations', [
            'hgnc_id' => 17098,
            'mondo_id' => 'MONDO:8888888',
        ]);
    }
    

    /**
     * @test
     */
    public function adds_status_on_update_message()
    {
        $curation = $this->createDICER1();

        $payload = json_decode(file_get_contents($this->provisionalMsgPath));
        $kafkaMessage = $this->makeMessage(json_encode($payload));
        $event = new Received($kafkaMessage);

        event($event);

        $this->assertDatabaseHas('curation_curation_status', [
            'curation_id' => $curation->id,
            'curation_status_id' => config('curations.statuses.curation-provisional'),
            'status_date' => Carbon::parse($payload->date)->startOfDay()->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * @test
     */
    public function does_not_create_duplicate_status()
    {
        $curation = $this->createDICER1();
        $curationStatus = CurationStatus::find(config('curations.statuses.approved'));
        AddStatus::dispatchSync($curation, $curationStatus, '2019-01-08 18:16:30');

        $payload = json_decode(file_get_contents($this->approvedWithStatusDateMsgPath));
        $kafkaMessage = $this->makeMessage(json_encode($payload));
        $event = new Received($kafkaMessage);

        event($event);

        $expected = [
            'curation_id' => $curation->id,
            'curation_status_id' => config('curations.statuses.approved'),
            'status_date' => '2019-01-08 00:00:00',
        ];
        $this->assertDatabaseHas('curation_curation_status', $expected);

        $this->assertEquals(1, \DB::table('curation_curation_status')->where($expected)->count());
    }

    /**
     * @test
     */
    public function unpublished_gci_status_maps_to_published_gt_status()
    {
        $curation = $this->createDICER1();

        $payload = json_decode(file_get_contents($this->provisionalMsgPath));

        $payload->status = 'unpublished';
        $kafkaMessage = $this->makeMessage(json_encode($payload));
        $event = new Received($kafkaMessage);

        event($event);

        $this->assertDatabaseHas('curation_curation_status', [
            'curation_id' => $curation->id,
            'curation_status_id' => config('curations.statuses.published'),
            'status_date' => Carbon::parse($payload->date)->startOfDay()->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * @test
     */
    public function updates_classification_if_updated()
    {
        $curation = $this->createDICER1();

        $payload = json_decode(file_get_contents($this->provisionalMsgPath));
        $kafkaMessage = $this->makeMessage(json_encode($payload));
        $event = new Received($kafkaMessage);

        event($event);

        $this->assertDatabaseHas('classification_curation', [
            'curation_id' => $curation->id,
            'classification_id' => config('project.classifications.limited'),
            'classification_date' => Carbon::parse($payload->date),
        ]);
    }

    /**
     * @test
     */
    public function uses_status_name_and_date_if_oject()
    {
        $curation = $this->createDICER1();

        $payload = $this->fireTestEvent($this->approvedWithStatusDateMsgPath);

        $this->assertDatabaseHas('classification_curation', [
            'curation_id' => $curation->id,
            'classification_id' => config('project.classifications.limited'),
            'classification_date' => Carbon::parse($payload->status->date)->format('Y-m-d H:i:s'),
        ]);

        $this->assertDatabaseHas('curation_curation_status', [
            'curation_id' => $curation->id,
            'curation_status_id' => config('curations.statuses.curation-approved'),
            'status_date' => Carbon::parse($payload->status->date)->startOfDay()->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * @test
     */
    public function sets_new_owner_if_transfer_message()
    {
        Carbon::setTestNow('2021-05-04');

        $affiliation1 = factory(Affiliation::class)->create([ 'clingen_id' => '40001' ]);
        $expertPanel1 = factory(ExpertPanel::class, )->create(['affiliation_id' => $affiliation1->id]);
        
        $affiliation2 = factory(Affiliation::class)->create([ 'clingen_id' => '40002' ]);
        $expertPanel2 = factory(ExpertPanel::class, )->create(['affiliation_id' => $affiliation2->id]);
        
        $curation = $this->createDICER1();
        SetOwner::dispatchSync($curation, $expertPanel1->id, Carbon::now());
        Carbon::setTestNow('2022-07-08');

        $this->fireTestEvent($this->gdmTransfered);

        $this->assertDatabaseHas('curation_expert_panel', [
            'curation_id' => $curation->id,
            'expert_panel_id' => $expertPanel1->id,
            'start_date' => Carbon::parse('2021-05-04'),
            'end_date' => Carbon::now(),
        ]);

        $this->assertDatabaseHas('curation_expert_panel', [
            'curation_id' => $curation->id,
            'expert_panel_id' => $expertPanel2->id,
            'start_date' => Carbon::now(),
            'end_date' => null,
        ]);
    }

    /**
     * @test
     */
    public function stores_notes_on_curation_if_sent_in_transfer_message()
    {
        Carbon::setTestNow('2021-05-04');

        $affiliation1 = factory(Affiliation::class)->create([ 'clingen_id' => '40001' ]);
        $expertPanel1 = factory(ExpertPanel::class, )->create(['affiliation_id' => $affiliation1->id]);
        
        $affiliation2 = factory(Affiliation::class)->create([ 'clingen_id' => '40002' ]);
        $expertPanel2 = factory(ExpertPanel::class, )->create(['affiliation_id' => $affiliation2->id]);
        
        $curation = $this->createDICER1();
        SetOwner::dispatchSync($curation, $expertPanel1->id, Carbon::now());
        Carbon::setTestNow('2022-07-08');

        $this->fireTestEvent($this->gdmTransfered);

        $this->assertDatabaseHas('notes', [
            'subject_type' => 'App\Curation',
            'subject_id' => $curation->id,
            'content' => 'Transferred from Test GCEP 2 to Test GCEP 1.',
            'topic' => 'curation transfer (via GCI)'
        ]);
    }
    

    /**
     * @test
     */
    public function updates_mondo_id_if_disease_change_message()
    {
        $newDisease = factory(Disease::class)->create(['mondo_id' => 'MONDO:0012377']);
        $curation = $this->createDICER1();
        $curation->update(['mondo_id' => 'MONDO:0012399']);

        $this->fireTestEvent($this->diseaseChanged);

        $this->assertDatabaseHas('curations', [
            'id' => $curation->id,
            'mondo_id' => $newDisease->mondo_id
        ]);
    }
    

    private function fireTestEvent($messagePath)
    {
        $message = $this->makeMessage(file_get_contents($messagePath));
        $event = new Received($message);

        event($event);

        return json_decode($message->payload);
    }
    
    private function createDICER1()
    {
        return factory(Curation::class)->create([
            'gene_symbol' => 'DICER1',
            'hgnc_id' => 17098,
            'mondo_id' => 'MONDO:0011111',
        ]);
    }

    private function makeMessage($payload)
    {
        $kafkaMessage = new class {
            public $payload;

            public function errstr() {
                return 'error string!';
            }
            public function headers () {
                return 'headers!';
            }
        };

        $kafkaMessage->payload = $payload;

        return $kafkaMessage;
    }
}
