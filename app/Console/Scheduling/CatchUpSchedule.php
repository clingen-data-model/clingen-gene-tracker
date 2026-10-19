<?php

namespace App\Console\Scheduling;

use Illuminate\Console\Scheduling\Schedule;

/**
 * Creates CatchUpEvents so schedule:run can be triggered infrequently.
 */
class CatchUpSchedule extends Schedule
{
    // Mirrors Schedule::exec(); revisit on Laravel upgrades.
    public function exec($command, array $parameters = [])
    {
        if ($parameters !== []) {
            $command .= ' '.$this->compileParameters($parameters);
        }

        $this->events[] = $event = new CatchUpEvent($this->eventMutex, $command, $this->timezone);

        $this->mergePendingAttributes($event);

        return $event;
    }
}
