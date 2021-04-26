<?php

namespace Tests\Unit\Listeners\Curations;

use App\Affiliation;
use App\Curation;
use App\CurationStatus;
use App\Events\StreamMessages\Received;
use App\Jobs\Curations\AddStatus;
use App\ModeOfInheritance;
use App\StreamError;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

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
            'curation_status_id' => config('project.curation-statuses.curation-provisional'),
            'status_date' => Carbon::parse($payload->date)->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * @test
     */
    public function does_not_create_duplicate_status()
    {
        $curation = $this->createDICER1();
        $curationStatus = CurationStatus::find(config('project.curation-statuses.approved'));
        AddStatus::dispatchNow($curation, $curationStatus, '2019-01-08 18:16:30');

        $payload = json_decode(file_get_contents($this->approvedWithStatusDateMsgPath));
        $kafkaMessage = $this->makeMessage(json_encode($payload));
        $event = new Received($kafkaMessage);

        event($event);

        $expected = [
            'curation_id' => $curation->id,
            'curation_status_id' => config('project.curation-statuses.approved'),
            'status_date' => '2019-01-08 18:16:30',
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
            'curation_status_id' => config('project.curation-statuses.published'),
            'status_date' => Carbon::parse($payload->date)->format('Y-m-d H:i:s'),
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

        $payload = json_decode(file_get_contents($this->approvedWithStatusDateMsgPath));
        $kafkaMessage = $this->makeMessage(json_encode($payload));
        $event = new Received($kafkaMessage);

        event($event);

        $this->assertDatabaseHas('classification_curation', [
            'curation_id' => $curation->id,
            'classification_id' => config('project.classifications.limited'),
            'classification_date' => Carbon::parse($payload->status->date)->format('Y-m-d H:i:s'),
        ]);

        $this->assertDatabaseHas('curation_curation_status', [
            'curation_id' => $curation->id,
            'curation_status_id' => config('project.curation-statuses.curation-approved'),
            'status_date' => Carbon::parse($payload->status->date)->format('Y-m-d H:i:s'),
        ]);
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
        $kafkaMessage = new \RdKafka\Message();
        $kafkaMessage->payload = $payload;

        return $kafkaMessage;
    }
}
