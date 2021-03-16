<?php

namespace App\Jobs\Curations;

use App\Curation;
use App\Hgnc\HgncClient;
use OutOfBoundsException;
use Illuminate\Bus\Queueable;
use App\Hgnc\HgncClientContract;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use App\Exceptions\HttpNotFoundException;
use App\Exceptions\ApiServerErrorException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Jobs\NotifyCoordinatorsAboutCuration;
use App\Notifications\Curations\GeneSymbolUpdated;

class AugmentWithHgncInfo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $curation;
    protected $attempts = 0;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Curation $curation)
    {
        //
        $this->curation = $curation;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(HgncClientContract $hgncClient)
    {
        try {
            $this->updateCurationWithSymbol($hgncClient);
        } catch (HttpNotFoundException $e) {
            try {
                $this->updateCurationWithPrevSymbol($hgncClient);
            } catch (HttpNotFoundException $e) {
                throw $e;
            }
        }
    }

    private function updateCurationWithSymbol($hgncClient)
    {
        $geneSymbolRecord = $hgncClient->fetchGeneSymbol($this->curation->gene_symbol);
        $this->curation->update([
            'hgnc_name' => $geneSymbolRecord->name,
            'hgnc_id' => $geneSymbolRecord->hgnc_id
        ]);
    }

    private function updateCurationWithPrevSymbol($hgncClient)
    {
        $oldGeneSymbol = $this->curation->gene_symbol;
        $prevSymbolRecord = $hgncClient->fetchPreviousSymbol($this->curation->gene_symbol);
        $this->curation->update([
            'gene_symbol' => $prevSymbolRecord->symbol,
            'hgnc_name' => $prevSymbolRecord->name,
            'hgnc_id' => $prevSymbolRecord->hgnc_id
        ]);

        NotifyCoordinatorsAboutCuration::dispatch($this->curation, GeneSymbolUpdated::class, $oldGeneSymbol);
    }
}
