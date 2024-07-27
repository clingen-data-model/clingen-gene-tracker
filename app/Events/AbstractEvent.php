<?php
namespace App\Events;

use App\Events\Event;
use Ramsey\Uuid\Uuid;

class AbstractEvent implements Event
{
    private $eventUuid;

    public function getEventUuid(): string
    {
        if (is_null($this->eventUuid)) {
            $this->eventUuid = Uuid::uuid4()->toString();
        }
        return $this->eventUuid;
    }
}