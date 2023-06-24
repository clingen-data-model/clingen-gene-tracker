<?php

namespace App\Events\Disease;

use App\Disease;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MondoTermObsoleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $disease;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Disease $disease)
    {
        //
        $this->disease = $disease;
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
