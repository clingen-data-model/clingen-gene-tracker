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
            $curation->phenotypes->each(function ($pheno) use ($curation, $omim) {
                dump('$curation->gene_symbol: '.$curation->gene_symbol);
                dump('$pheno->mim_number: '.$pheno->mim_number);
                // dump($omim->getGenePhenotypes($curation->gene_symbol));
                if (empty($pheno->name)) {
                    // dump($pheno->mim_number);
                    $genePhenos = $omim->getGenePhenotypes($curation->gene_symbol);
                    // dump($genePhenos);
                    $genePheno = $genePhenos->filter(function ($gp) use ($pheno) {
                        dump($gp->phenotypeMimNumber.' == '.$pheno->mim_number);
                        return $gp->phenotypeMimNumber == $pheno->mim_number;
                    })->first();

                    // dump($genePheno);
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
