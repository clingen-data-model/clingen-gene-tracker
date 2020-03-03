<?php

namespace App\Listeners\Curations;

use App\Curation;
use App\Events\StreamMessages\Received;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateFromStreamMessage
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
     * @param  Received  $event
     * @return void
     */
    public function handle(Received $event)
    {
        $message = $event->message;

        dd($message);

        // if ($message->status == 'created') {
        //     $curation = Curation::where([
        //         'hgnc_id' => $message->
        //     ])
        // }

        // $curation = Curation::findByGuid($message->report_id);
        // if (!$curation) {
        //     throw new UnkownCurationRecordException();
        // }
    }
}
