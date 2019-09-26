<?php

namespace App\Mail\Curations;

use App\Curation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class GeneSymbolUpdated extends Mailable
{
    use Queueable, SerializesModels;

    private $curation;
    private $oldGeneSymbol;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Curation $curation, string $oldGeneSymbol)
    {
        $this->curation = $curation;
        $this->oldGeneSymbol = $oldGeneSymbol;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this
                ->view('email.curations.gene_symbol_updated')
                ->with(['oldGeneSymbol' => $this->oldGeneSymbol, 'curation' => $this->curation]);
    }
}
