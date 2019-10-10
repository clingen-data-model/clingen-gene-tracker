<?php

namespace App\Mail;

use App\Curation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class HgncIdNotFoundNotification extends Mailable
{
    use Queueable, SerializesModels;

    protected $curation;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Curation $curation)
    {
        //
        $this->curation = $curation;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('There\'s an issue with one of your curations')
            ->view('email.curations.hgnc_id_not_found')
            ->with(['curation' => $this->curation]);
    }
}
