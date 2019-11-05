<?php

namespace App\Mail\Curations;

use App\Curation;
use App\Phenotype;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class PhenotypeOmimEntryMoved extends Mailable
{
    use Queueable, SerializesModels;

    private $curation;
    private $phenotype;
    private $oldName;
    private $oldMimNumber;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Curation $curation, Phenotype $phenotype, string $oldName, int $oldMimNumber)
    {
        //
        $this->curation = $curation;
        $this->oldName = $oldName;
        $this->phenotype = $phenotype;
        $this->oldMimNumber = $oldMimNumber;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('email.curations.omim_entry_moved')
            ->with([
                'oldName' => $this->oldName,
                'oldMimNumber' => $this->oldMimNumber,
                'phenotype' => $this->phenotype,
                'curation' => $this->curation
            ]);
    }
}
