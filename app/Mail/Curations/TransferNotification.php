<?php

namespace App\Mail\Curations;

use App\Curation;
use App\ExpertPanel;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class TransferNotification extends Mailable
{
    use Queueable, SerializesModels;

    public Curation $curation;
    public ExpertPanel $previousEp;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Curation $curation, ExpertPanel $previousEp)
    {
        $this->curation = $curation;
        $this->previousEp = $previousEp;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $this->previousEp
            ->coordinators
            ->each( function ($c) {
                $this->cc($c->email, $c->name);
            });

            $this
                ->view('email.curations.transfer_notification')
                ->with([
                    'curation' => $this->curation,
                    'previousEp' => $this->previousEp
                ]);
    }
}
