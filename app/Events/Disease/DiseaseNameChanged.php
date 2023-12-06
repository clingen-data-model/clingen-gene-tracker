<?php

namespace App\Events\Disease;

use App\Disease;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DiseaseNameChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $disease;

    public $oldName;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Disease $disease, $oldName)
    {
        $this->disease = $disease;
        $this->oldName = $oldName;
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
