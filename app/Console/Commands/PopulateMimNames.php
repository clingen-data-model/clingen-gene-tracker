<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Contracts\OmimClient;
use App\Phenotype;
use App\Curation;

class PopulateMimNames extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'omim:get-names';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(OmimClient $omim)
    {
        $curations = Curation::with('phenotypes');
        $curations->each(function ($curation) use ($omim) {
            $curation->selectedPhenotypes->each(function ($pheno) use ($curation, $omim) {
                if (empty($pheno->name)) {
                    $genePhenos = $omim->getGenePhenotypes($curation->gene_symbol);
                    $genePheno = $genePhenos->filter(function ($gp) use ($pheno) {
                        return $gp->phenotypeMimNumber == $pheno->mim_number;
                    })->first();

                    if ($genePheno) {
                        $pheno->update([
                            'name' => $genePheno->phenotype
                        ]);
                    }
                }
            });
        });
    }
}
