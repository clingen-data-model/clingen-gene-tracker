<?php

namespace Tests\Unit\Console;

use App\Console\Scheduling\CatchUpSchedule;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CatchUpScheduleTest extends TestCase
{
    private CatchUpSchedule $schedule;

    public function setUp(): void
    {
        parent::setUp();
        config(['app.schedule_catch_up_minutes' => 60]);
        $this->schedule = new CatchUpSchedule('UTC');
    }

    public function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function dueCommandsAt(string $time): array
    {
        Carbon::setTestNow(Carbon::parse($time, 'UTC'));

        return $this->schedule->dueEvents($this->app)
            ->map(fn ($event) => $event->command)
            ->values()
            ->all();
    }

    #[Test]
    public function kernel_resolves_catch_up_schedule()
    {
        $this->assertInstanceOf(CatchUpSchedule::class, $this->app->make(Schedule::class));
    }

    #[Test]
    public function runs_late_event_within_window_exactly_once()
    {
        $this->schedule->exec('daily-task')->dailyAt('03:00');

        $this->assertSame([], $this->dueCommandsAt('2026-10-19 02:59:00'));
        $this->assertSame(['daily-task'], $this->dueCommandsAt('2026-10-19 03:07:00'));
        $this->assertSame([], $this->dueCommandsAt('2026-10-19 03:20:00'));
    }

    #[Test]
    public function skips_occurrence_older_than_window()
    {
        $this->schedule->exec('daily-task')->dailyAt('03:00');

        $this->assertSame([], $this->dueCommandsAt('2026-10-19 04:01:00'));
    }

    #[Test]
    public function collapses_missed_occurrences_into_one_run()
    {
        $this->schedule->exec('frequent-task')->everyTenMinutes();

        $this->assertSame(['frequent-task'], $this->dueCommandsAt('2026-10-19 03:00:00'));
        $this->assertSame(['frequent-task'], $this->dueCommandsAt('2026-10-19 03:25:00'));
        $this->assertSame([], $this->dueCommandsAt('2026-10-19 03:29:00'));
        $this->assertSame(['frequent-task'], $this->dueCommandsAt('2026-10-19 03:30:00'));
    }

    #[Test]
    public function zero_window_matches_only_the_current_minute()
    {
        config(['app.schedule_catch_up_minutes' => 0]);
        $this->schedule->exec('daily-task')->dailyAt('03:00');

        $this->assertSame([], $this->dueCommandsAt('2026-10-19 03:01:00'));
        $this->assertSame(['daily-task'], $this->dueCommandsAt('2026-10-20 03:00:40'));
    }

    #[Test]
    public function uses_event_timezone()
    {
        $this->schedule->exec('eastern-task')->dailyAt('03:00')->timezone('America/New_York');

        $this->assertSame([], $this->dueCommandsAt('2026-10-19 03:05:00'));
        $this->assertSame(['eastern-task'], $this->dueCommandsAt('2026-10-19 07:05:00'));
    }

    #[Test]
    public function respects_event_environments()
    {
        $this->schedule->exec('prod-task')->dailyAt('03:00')->environments('production');

        $this->assertSame([], $this->dueCommandsAt('2026-10-19 03:00:00'));
    }
}
