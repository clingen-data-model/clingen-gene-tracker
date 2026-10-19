<?php

namespace App\Console\Scheduling;

use Cron\CronExpression;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;

use function Illuminate\Support\enum_value;

/**
 * schedule:run is triggered by an OpenShift CronJob rather than every minute,
 * and the job can start late. Instead of requiring an event's cron expression
 * to match the current minute, an event is due when its most recent occurrence
 * falls within the catch-up window and has not been claimed yet. Claims are
 * atomic cache adds, so overlapping runs cannot both run an occurrence.
 */
class CatchUpSchedule extends Schedule
{
    protected $claimStore = null;

    public function useCache($store)
    {
        $this->claimStore = enum_value($store);

        return parent::useCache($store);
    }

    public function dueEvents($app)
    {
        $now = Date::now();
        $window = (int) config('app.schedule_catch_up_minutes');

        return (new Collection($this->events))->filter(function (Event $event) use ($app, $now, $window) {
            if (! $event->runsInMaintenanceMode() && $app->isDownForMaintenance()) {
                return false;
            }

            if (! $event->runsInEnvironment($app->environment())) {
                return false;
            }

            $due = $this->latestOccurrence($event, $now);

            if ($due->lt($now->copy()->startOfMinute()->subMinutes($window))) {
                return false;
            }

            return Cache::store($this->claimStore)->add(
                'schedule-occurrence:'.$event->mutexName().':'.$due->getTimestamp(),
                true,
                $now->copy()->addMinutes($window * 2 + 1)
            );
        });
    }

    protected function latestOccurrence(Event $event, $now)
    {
        $local = $event->timezone ? $now->copy()->setTimezone($event->timezone) : $now->copy();

        return Date::instance(
            (new CronExpression($event->expression))->getPreviousRunDate($local, 0, true)
        );
    }
}
