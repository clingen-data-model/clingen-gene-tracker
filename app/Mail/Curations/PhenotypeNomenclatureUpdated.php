<?php

namespace App\Mail\Curations;

use App\Curation;
use App\Phenotype;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class PhenotypeNomenclatureUpdated extends Mailable
{
    use Queueable, SerializesModels;

    private $curation;
    private $oldName;
    private $phenotype;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Curation $curation, Phenotype $phenotype, $oldName)
    {
        //
        $this->curation = $curation;
        $this->oldName = $oldName;
        $this->phenotype = $phenotype;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('email.curations.omim_phenotype_updated')
                ->with([
                    'curation' => $this->curation,
                    'oldName' => $this->oldName,
                    'phenotype' => $this->phenotype
                ]);
    }
}
