<?php

namespace App\Events\Curation;

use App\Curation;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

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
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
