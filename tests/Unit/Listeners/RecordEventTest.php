<?php

namespace Tests\Unit\Listeners;

use App\User;
use App\Activity;
use Tests\TestCase;
use App\Listeners\RecordEvent;
use Illuminate\Support\Carbon;
use App\Events\RecordableEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class RecordEventTest extends TestCase
{
    use DatabaseTransactions;

    private $event, $listener;

    public function setup(): void
    {
        parent::setup();
        $this->event = $this->createDummyEvent();
        $this->listener = new RecordEvent();
    }

    /**
     * @test
     */
    public function it_records_event_uuid()
    {
        $this->listener->handle($this->event);

        $this->assertEquals('test_uuid', Activity::first()->event_uuid);
    }

    /**
     * @test
     */
    public function when_no_authed_user_it_does_not_record_causer():void
    {
        $this->listener->handle($this->event);

        $this->assertNull(Activity::first()->causer_id);
    }

    /**
     * @test
     */
    public function when_authed_user_it_records_causer():void
    {
        $user = $this->setupUser();

        $this->actingAs($user);

        $this->listener->handle($this->event);

        $this->assertEquals($user->id, Activity::first()->causer_id);
    }

    /**
     * @test
     */
    public function it_records_properties():void
    {
        $this->listener->handle($this->event);

        $this->assertEquals('test_value', Activity::first()->properties['test_property']);
    }
    
    /**
     * @test
     */
    public function it_records_the_subject():void
    {
        $this->listener->handle($this->event);

        $this->assertEquals(User::class, Activity::first()->subject_type);
        $this->assertEquals(999, Activity::first()->subject_id);
    }

    /**
     * @test
     */
    public function it_records_the_log_entry():void
    {
        $this->listener->handle($this->event);

        $this->assertEquals($this->event->getLogEntry(), Activity::first()->description);
    }
    
    /**
     * @test
     */
    public function it_sets_the_event_log_date_as_created_at():void
    {
        $this->listener->handle($this->event);

        $this->assertEquals($this->event->getLogDate(), Activity::first()->created_at);
    }
    

    private function createDummyEvent()
    {
        return new class extends RecordableEvent {
            public function getLogEntry(): string
            {
                return 'test_log_entry';
            }

            public function getLog(): string
            {
                return 'test_log';
            }

            public function hasSubject(): bool
            {
                return true;
            }

            public function getSubject(): Model
            {
                return factory(User::class)->create(['id' => 999]);
            }

            public function getProperties(): ?array
            {
                return ['test_property' => 'test_value'];
            }

            public function getLogDate(): Carbon
            {
                return Carbon::parse('2021-01-01 00:00:00');
            }

            public function getEventUuid(): string
            {
                return 'test_uuid';
            }
        };
    }
}
