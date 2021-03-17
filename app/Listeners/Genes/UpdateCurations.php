<?php

namespace App\Listeners\Genes;

use App\Events\Genes\GeneSymbolChanged;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class UpdateCurations
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  GeneSymbolChanged  $event
     * @return void
     */
    public function handle(GeneSymbolChanged $event)
    {
        //
    }
}
