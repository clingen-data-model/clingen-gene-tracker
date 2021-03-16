<?php

namespace App\Events\Genes;

use App\Gene;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class GeneSymbolChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Gene $gene;
    
    public String $previousSymbol;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Gene $gene, String $previousSymbol)
    {
        $this->gene = $gene;
        $this->previousSymbol = $previousSymbol;
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
