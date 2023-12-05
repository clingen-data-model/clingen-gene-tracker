<?php

namespace App\Events\Curation;

use App\Curation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class Saving
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $curation;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Curation $curation)
    {
        $this->curation = $curation;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn(): array
    {
        return new PrivateChannel('channel-name');
    }
}
