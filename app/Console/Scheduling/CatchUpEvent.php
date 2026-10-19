<?php

namespace App\Console\Scheduling;

use Cron\CronExpression;
use Illuminate\Console\Application;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;

/**
 * Due when the expression's most recent due time is later than the command's
 * last recorded start in command_resource_usages, rather than only during the
 * exact due minute. Falls back to exact-minute matching without recorded runs.
 */
class CatchUpEvent extends Event
{
    protected function expressionPasses()
    {
        $lastStart = $this->lastRecordedStart();

        if ($lastStart === null) {
            return parent::expressionPasses();
        }

        $lastDue = (new CronExpression($this->expression))
            ->getPreviousRunDate(Date::now(), 0, true, $this->timezone);

        return $lastDue > Date::parse($lastStart);
    }

    private function lastRecordedStart(): ?string
    {
        $prefix = Application::formatCommandString('');

        if (! config('logging.record_command_resource_usage') || ! str_starts_with((string) $this->command, $prefix)) {
            return null;
        }

        $name = Str::before(trim(Str::after($this->command, $prefix)), ' ');
        $runs = DB::table('command_resource_usages');

        // Never-recorded commands wait for their first slot after recording began.
        return (clone $runs)->where('command', $name)->max('started_at') ?? $runs->min('started_at');
    }
}
