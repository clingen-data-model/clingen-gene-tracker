<?php

namespace App\Jobs\Curations;

use App\Gene;
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
    public function handle()
    {
        try {
            $this->updateCurationWithSymbol();
        } catch (HttpNotFoundException $e) {
            try {
                $this->updateCurationWithPrevSymbol();
            } catch (HttpNotFoundException $e) {
                throw $e;
            }
        }
    }

    private function updateCurationWithSymbol()
    {
        $gene = Gene::findBySymbol($this->curation->gene_symbol);
        if (!$gene) {
            throw new HttpNotFoundException();
        }
        $this->curation->update([
            'hgnc_name' => $gene->hgnc_name,
            'hgnc_id' => $gene->hgnc_id
        ]);
    }

    private function updateCurationWithPrevSymbol()
    {
        $oldGeneSymbol = $this->curation->gene_symbol;
        // $prevSymbolRecord = $hgncClient->fetchPreviousSymbol($this->curation->gene_symbol);
        $prevSymbolRecord = Gene::findByPreviousSymbol($this->curation->gene_symbol);
        if (!$prevSymbolRecord) {
            throw new HttpNotFoundException();
        }

        $this->curation->update([
            'gene_symbol' => $prevSymbolRecord->gene_symbol,
            'hgnc_name' => $prevSymbolRecord->hgnc_name,
            'hgnc_id' => $prevSymbolRecord->hgnc_id
        ]);

        NotifyCoordinatorsAboutCuration::dispatch($this->curation, GeneSymbolUpdated::class, $oldGeneSymbol);
    }
}
