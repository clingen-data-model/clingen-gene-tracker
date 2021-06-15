<?php

namespace App\Jobs\Curations;

use App\Gene;
use App\Curation;
use OutOfBoundsException;
use App\Exceptions\HttpNotFoundException;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Jobs\NotifyCoordinatorsAboutCuration;
use App\Notifications\Curations\GeneSymbolUpdated;

class AugmentWithHgncInfo
{
    use Dispatchable;

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
    public function handle()
    {
        if (
            $this->curation->isDirty('gene_symbol')
            || is_null($this->curation->hgnc_id)
            || is_null($this->curation->hgnc_name)
        ) {
            try {
                $this->updateCurationWithSymbol();
            } catch (HttpNotFoundException $e) {
                try {
                    $this->updateCurationWithPrevSymbol();
                } catch (HttpNotFoundException $e) {
                    \Log::warning($e->getMessage());
                }
            }
        }
    }

    private function updateCurationWithSymbol()
    {
        $gene = Gene::findBySymbol($this->curation->gene_symbol);
        if (!$gene) {
            throw new HttpNotFoundException('unable to add hgnc_id and hgnc_name to curation '.$this->curation->id.' with gene_symbol '.$this->curation->gene_symbol.'.');
        }
        $this->curation->hgnc_name = $gene->hgnc_name;
        $this->curation->hgnc_id = $gene->hgnc_id;
    }

    private function updateCurationWithPrevSymbol()
    {
        $oldGeneSymbol = $this->curation->gene_symbol;
        // $prevSymbolRecord = $hgncClient->fetchPreviousSymbol($this->curation->gene_symbol);
        $prevSymbolRecord = Gene::findByPreviousSymbol($this->curation->gene_symbol);
        if (!$prevSymbolRecord) {
            throw new HttpNotFoundException('No previous symbol found for un-resolvable gene symbol '.$this->curation->gene_symbol.' on curation '.$this->curation->id.'.');
        }

        $this->curation->fill([
            'gene_symbol' => $prevSymbolRecord->gene_symbol,
            'hgnc_name' => $prevSymbolRecord->hgnc_name,
            'hgnc_id' => $prevSymbolRecord->hgnc_id
        ]);

        NotifyCoordinatorsAboutCuration::dispatch($this->curation, GeneSymbolUpdated::class, $oldGeneSymbol);
    }
}
