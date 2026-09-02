<?php

namespace Tests\Unit\DataExchange\Kafka;

use App\DataExchange\Events\Received;
use App\DataExchange\Kafka\StoreMessageHandler;
use App\DataExchange\Kafka\SuccessfulMessageHandler;
use App\IncomingStreamMessage;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * Kafka delivery is at-least-once, so the transport layer has to recognise a
 * message it has already consumed. Intentional re-processing goes through
 * gci:replay, which reads incoming_stream_messages directly and never reaches
 * this chain.
 *
 * @group gci
 */
class StoreMessageHandlerTest extends TestCase
{
    private const KEY = 'report-1-2020-01-01T00:00:00Z';

    /**
     * @test
     */
    public function stores_and_dispatches_a_message_it_has_not_seen()
    {
        Event::fake([Received::class]);

        $this->handler()->handle($this->message('report-1', '2020-01-01T00:00:00Z'));

        $this->assertDatabaseHas('incoming_stream_messages', ['key' => self::KEY]);
        Event::assertDispatchedTimes(Received::class, 1);
    }

    /**
     * @test
     */
    public function does_not_dispatch_a_message_it_has_already_consumed()
    {
        Event::fake([Received::class]);
        $handler = $this->handler();

        $handler->handle($this->message('report-1', '2020-01-01T00:00:00Z'));
        $handler->handle($this->message('report-1', '2020-01-01T00:00:00Z'));

        $this->assertEquals(1, IncomingStreamMessage::where('key', self::KEY)->count());
        Event::assertDispatchedTimes(Received::class, 1);
    }

    /**
     * A key that returns with different content is ambiguous, not fatal. This used
     * to call die() and take the consumer process down mid-loop.
     *
     * @test
     */
    public function does_not_dispatch_or_die_when_a_key_returns_with_a_different_payload()
    {
        Event::fake([Received::class]);
        $handler = $this->handler();

        $handler->handle($this->message('report-1', '2020-01-01T00:00:00Z'));
        $handler->handle($this->message('report-1', '2020-01-01T00:00:00Z', ['extra' => 'changed']));

        Event::assertDispatchedTimes(Received::class, 1);
        $this->assertEquals(1, IncomingStreamMessage::where('key', self::KEY)->count());
    }

    /**
     * Built per test rather than in setUp: SuccessfulMessageHandler captures the
     * dispatcher when it is constructed, so it has to be made after Event::fake().
     */
    private function handler(): StoreMessageHandler
    {
        $handler = new StoreMessageHandler();
        $handler->setNext(app(SuccessfulMessageHandler::class));

        return $handler;
    }

    private function message(string $reportId, string $date, array $extra = []): \RdKafka\Message
    {
        $message = new \RdKafka\Message();
        $message->payload = json_encode(array_merge(['report_id' => $reportId, 'date' => $date], $extra));
        $message->topic_name = 'gene_validity_events';
        $message->partition = 0;
        $message->offset = 0;
        $message->err = RD_KAFKA_RESP_ERR_NO_ERROR;
        $message->timestamp = 0;

        return $message;
    }
}
