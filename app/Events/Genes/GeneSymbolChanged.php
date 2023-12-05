<?php

namespace App\Events\Genes;

use App\Gene;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

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
    public function __construct(Gene $gene, string $previousSymbol)
    {
        $this->gene = $gene;
        $this->previousSymbol = $previousSymbol;
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
