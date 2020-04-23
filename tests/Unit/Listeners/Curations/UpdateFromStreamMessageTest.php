<?php

namespace Tests\Unit\Listeners\Curations;

use Bus;
use App\Curation;
use Carbon\Carbon;
use Tests\TestCase;
use App\Affiliation;
use App\Classification;
use App\StreamError;
use App\ModeOfInheritance;
use App\Jobs\Curations\AddStatus;
use App\Events\StreamMessages\Received;
use App\Jobs\Curations\AddClassification;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

/**
 * @group gci
 * @group curations
 */
class UpdateFromStreamMessageTest extends TestCase
{
    use DatabaseTransactions;

    public function setup():void
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
            'notification_sent_at' => null
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
            'moi_id' => ModeOfInheritance::findByHpId($payload->gene_validity_evidence_level->genetic_condition->mode_of_inheritance)->id
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
     * @test
     */
    public function never_updates_mondo_id_on_curation()
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
            'status_date' => Carbon::parse($payload->date)->format('Y-m-d H:i:s')
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
            'classification_date' => Carbon::parse($payload->date)
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
            'classification_date' => Carbon::parse($payload->status->date)->format('Y-m-d H:i:s')
        ]);


        $this->assertDatabaseHas('curation_curation_status', [
            'curation_id' => $curation->id,
            'curation_status_id' => config('project.curation-statuses.curation-approved'),
            'status_date' => Carbon::parse($payload->status->date)->format('Y-m-d H:i:s')
        ]);
    }
    

    private function createDICER1()
    {
        return factory(Curation::class)->create([
            'gene_symbol' => 'DICER1',
            'hgnc_id' => 17098,
            'mondo_id' => 'MONDO:0011111'
        ]);
    }
    

    private function makeMessage($payload)
    {
        $kafkaMessage = new \RdKafka\Message();
        $kafkaMessage->payload = $payload;

        return $kafkaMessage;
    }
}
