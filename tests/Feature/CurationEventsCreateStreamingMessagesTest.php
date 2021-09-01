<?php

namespace Tests\Feature;

use Mockery;
use App\Curation;
use Carbon\Carbon;
use Tests\TestCase;
use App\StreamMessage;
use App\CurationStatus;
use App\Jobs\Curations\AddStatus;
use Illuminate\Support\Facades\Bus;
use  App\DataExchange\Kafka\KafkaProducer;
use Illuminate\Foundation\Testing\WithFaker;
use App\DataExchange\Contracts\MessagePusher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\DataExchange\Exceptions\StreamingServiceException;

class CurationEventsCreateStreamingMessagesTest extends TestCase
{
    use DatabaseTransactions;

    protected $fakeCurationSavedEvent = false;

    /**
     * @test
     */
    public function creation_precuration_message_created_when_curation_created()
    {
        $curation = factory(Curation::class)->create();
        $curation->loadForMessage();
        $matchingMessages = StreamMessage::query()
                                ->topic(config('dx.topics.outgoing.precuration-events'))
                                ->where('message->data->id', $curation->id)
                                ->where('message->event_type', 'created')
                                ->get();
        $this->assertEquals(1, $matchingMessages->count());
    }
    
    /**
     * @test
     */
    public function updated_precuration_message_created_when_curation_updated()
    {
        $curation = factory(Curation::class)->create();
        $curation->update(['curation_notes' => 'test test test']);
        $this->assertDatabaseHas('stream_messages', [
            'message->event_type' => 'updated',
            'message->data->id' => $curation->id,
            'message->data->notes' => 'test test test',
            'topic' => config('dx.topics.outgoing.precuration-events'),

        ]);
    }

    /**
     * @test
     */
    public function gt_gci_message_created_when_curation_updated_with_GDM_and_curation_complete_status()
    {
        $curation = factory(Curation::class)->create();
        $curation->update(['gene_symbol' => 'TP53', 'hgnc_id'=>123, 'moi_id' => 1, 'mondo_id' => 'MONDO:1234566']);

        $this->assertDatabaseMissing('stream_messages', [
            'topic' => config('dx.topics.outgoing.gt-gci-sync')
        ]);

        $job = new AddStatus($curation, CurationStatus::find(config('curations.statuses.precuration-complete')));
        Bus::dispatch($job);

        $this->assertDatabaseHas('stream_messages', [
            'topic' => config('dx.topics.outgoing.gt-gci-sync'),
            'message->event_type' => 'precuration_completed',
            'message->data->uuid' => $curation->uuid,
        ]);
    }
    
    
    /**
     * @test
     */
    public function deleted_precuration_message_created_when_curation_deleted()
    {
        $curation = factory(Curation::class)->create();
        $curation->delete();
        $matchingMessages = StreamMessage::query()
                                ->where('message->data->id', $curation->id)
                                ->where('message->event_type', 'deleted')
                                // ->unsent()
                                ->get();

        $this->assertEquals(1, $matchingMessages->count());
    }

    /**
     * @test
     */
    public function message_pushed_to_streaming_service_when_created()
    {
        Carbon::setTestNow('2019-01-01');

        $mock = Mockery::mock(MessagePusher::class);
        $mock->shouldReceive([
            'topic' => $mock,
            'push' => null
        ]);
        $this->instance(MessagePusher::class, $mock);

        $message = factory(StreamMessage::class)->create();
    }

    /**
     * @test
     */
    public function sent_at_should_be_updated_when_message_successfully_pushed()
    {
        Carbon::setTestNow('2019-01-01');

        $message = factory(StreamMessage::class)->create();

        $this->assertDatabaseHas('stream_messages', [
            'id' => $message->id,
            'sent_at' => now()->format("Y-m-d H:i:s")
        ]);
    }
    

    /**
     * @test
     */
    public function sent_at_should_not_be_updated_when_message_send_fails()
    {
        \DB::table('stream_messages')->truncate();
        Carbon::setTestNow('2019-01-01');
        $mock = Mockery::mock(MessagePusher::class);
        $mock->shouldReceive('topic')->andThrow(new StreamingServiceException());

        $this->instance(MessagePusher::class, $mock);

        $message = factory(StreamMessage::class)->create(['sent_at' => null]);

        $this->assertDatabaseHas('stream_messages', [
            'id' => $message->id,
            'sent_at' => null
        ]);
    }
}
