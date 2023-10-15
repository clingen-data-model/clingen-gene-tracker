<?php

namespace App\Listeners\Genes;

use App\Events\Genes\GeneSymbolChanged;

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
     * @return void
     */
    public function handle(GeneSymbolChanged $event): void
    {
        //
    }
}
