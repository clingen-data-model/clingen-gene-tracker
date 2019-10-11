<?php

namespace Tests\Feature;

use Mockery;
use App\Curation;
use Carbon\Carbon;
use Tests\TestCase;
use App\StreamMessage;
use App\Services\KafkaProducer;
use App\Contracts\MessagePusher;
use Illuminate\Foundation\Testing\WithFaker;
use App\Exceptions\StreamingServiceException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class CurationEventsCreateStreamingMessagesTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @test
     */
    public function creation_StreamMessage_created_when_curation_created()
    {
        $curation = factory(Curation::class)->create();
        $curation->loadForMessage();
        $matchingMessages = StreamMessage::query()
                                ->where('message', 'LIKE', '%\"id\":'.$curation->id.'%')
                                ->where('message', 'LIKE', '%\"event\":\"created\"%')
                                // ->unsent()
                                ->get();
        // dd($matchingMessage);
        $this->assertEquals(1, $matchingMessages->count());
    }
    
    /**
     * @test
     */
    public function updated_StreamMessage_created_when_curation_created()
    {
        $curation = factory(Curation::class)->create();
        $curation->update(['notes' => 'test test test']);
        $curation->loadForMessage();
        $matchingMessages = StreamMessage::query()
                                ->where('message', 'LIKE', '%\"id\":'.$curation->id.'%')
                                ->where('message', 'LIKE', '%\"event\":\"updated\"%')
                                ->where('message', 'LIKE', '%\"notes\":\"test test test\"%')
                                // ->unsent()
                                ->get();
        // dd($matchingMessage);
        $this->assertEquals(1, $matchingMessages->count());
    }
    
    /**
     * @test
     */
    public function deleted_StreamMessage_created_when_curation_created()
    {
        $curation = factory(Curation::class)->create();
        $curation->delete();
        $matchingMessages = StreamMessage::query()
                                ->where('message', 'LIKE', '%\"id\":'.$curation->id.'%')
                                ->where('message', 'LIKE', '%\"event\":\"deleted\"%')
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
