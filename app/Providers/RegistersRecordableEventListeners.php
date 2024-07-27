<?php

namespace App\Providers;

use App\Listeners\RecordEvent;
use App\Events\RecordableEvent;
use Illuminate\Support\Facades\Event;

/**
 * Methods to register RecordableEvent listeners.
 */
trait RegistersRecordableEventListeners
{
    /**
     * Registers RecordEvent listener for all RecordableEvents in $eventClasses
     *
     * @param array $eventClasses Array of event classes.
     *
     * @return void
     */
    protected function registerRecordableEventListeners(array $eventClasses): void
    {
        foreach ($eventClasses as $class) {
            if (is_subclass_of($class, RecordableEvent::class)) {
                Event::listen($class, [RecordEvent::class, 'handle']);
            }
        }
    }


}
