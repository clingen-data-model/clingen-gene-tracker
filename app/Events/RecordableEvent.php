<?php

namespace App\Events;

// use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Str;
use App\Events\AbstractEvent;
use Illuminate\Support\Carbon;
use Illuminate\Queue\SerializesModels;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

abstract class RecordableEvent extends AbstractEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    abstract public function getLogEntry():string;

    public function getEventType():string
    {
        $type = Str::kebab(array_slice(explode('\\', get_class($this)), -1, 1)[0]);
        return $type;
    }

    abstract public function getLog():string;

    abstract public function hasSubject():bool;

    abstract public function getSubject():Model;

    abstract public function getProperties():?array;

    abstract public function getLogDate():Carbon;
}
