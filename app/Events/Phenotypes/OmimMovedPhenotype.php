<?php

namespace App\Events\Phenotypes;

use App\Phenotype;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OmimMovedPhenotype
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $phenotype;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Phenotype $phenotype)
    {
        //
        $this->phenotype = $phenotype;
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
