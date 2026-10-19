<?php

namespace Tests\Unit\Console\Scheduling;

use App\Console\Scheduling\CatchUpEvent;
use App\Console\Scheduling\CatchUpSchedule;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Date;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CatchUpEventTest extends TestCase
{
    use DatabaseTransactions;

    public function setUp(): void
    {
        parent::setUp();
        config(['logging.record_command_resource_usage' => true]);
        Date::setTestNow('2026-10-19 03:30:00');
    }

    public function tearDown(): void
    {
        Date::setTestNow();
        parent::tearDown();
    }

    private function daily(string $command, string $at = '01:00'): Event
    {
        return (new CatchUpSchedule())->command($command)->dailyAt($at);
    }

    private function recordRun(string $command, string $startedAt): void
    {
        DB::table('command_resource_usages')->insert(['command' => $command, 'started_at' => $startedAt]);
    }

    #[Test]
    public function app_schedule_creates_catch_up_events()
    {
        $schedule = $this->app->make(Schedule::class);

        $this->assertInstanceOf(CatchUpSchedule::class, $schedule);
        $this->assertNotEmpty($schedule->events());
        $this->assertContainsOnlyInstancesOf(CatchUpEvent::class, $schedule->events());
    }

    #[Test]
    public function due_when_not_started_since_last_due_time()
    {
        $this->recordRun('hgnc:update-data', '2026-10-18 01:00:05');

        $this->assertTrue($this->daily('hgnc:update-data')->isDue($this->app));
    }

    #[Test]
    public function not_due_when_started_since_last_due_time()
    {
        $this->recordRun('hgnc:update-data', '2026-10-19 01:00:05');

        $this->assertFalse($this->daily('hgnc:update-data')->isDue($this->app));
    }

    #[Test]
    public function matches_runs_by_command_name_ignoring_arguments()
    {
        $this->recordRun('notifications:clean', '2026-10-19 01:00:05');

        $this->assertFalse($this->daily('notifications:clean -f -q')->isDue($this->app));
    }

    #[Test]
    public function never_recorded_command_is_due_after_its_first_slot_since_recording_began()
    {
        $this->recordRun('gci:consume', '2026-10-19 01:30:00');

        $this->assertFalse($this->daily('hgnc:update-data', '01:00')->isDue($this->app));
        $this->assertTrue($this->daily('mondo:update-data', '02:00')->isDue($this->app));
    }

    #[Test]
    public function falls_back_to_exact_minute_without_recorded_runs()
    {
        $this->assertFalse($this->daily('hgnc:update-data', '01:00')->isDue($this->app));
        $this->assertTrue($this->daily('hgnc:update-data', '03:30')->isDue($this->app));
    }

    #[Test]
    public function falls_back_to_exact_minute_when_recording_is_disabled()
    {
        config(['logging.record_command_resource_usage' => false]);
        $this->recordRun('hgnc:update-data', '2026-10-18 01:00:05');

        $this->assertFalse($this->daily('hgnc:update-data', '01:00')->isDue($this->app));
        $this->assertTrue($this->daily('hgnc:update-data', '03:30')->isDue($this->app));
    }
}
