<?php

namespace App\Console\Commands;

use App\Phenotype;
use Carbon\Carbon;
use App\Contracts\OmimClient;
use Illuminate\Console\Command;
use App\Jobs\ImportOmimPhenotype;

class UpdateOmimMovedAndRemoved extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'omim:check-moved-and-removed';

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
    public function handle(OmimClient $omimClient)
    {
        $lastUpdated = $this->getLastUpdated();
        $searchResults = $omimClient->search([
            'search' => 'prefix:%5E AND date_updated:'.$lastUpdated->format('Y/m/d').'-*'
        ]);

        $phenotypes = $this->getPhenotypes($searchResults);
        $moveToPhenotypes = $this->getMovedToPhenotypes($searchResults);

        foreach ($searchResults as $item) {
            $pheno = $phenotypes->get($item->mimNumber);
            $pheno->omim_status = $item->status;

            if (isset($item->movedTo)) {
                if (!$moveToPhenotypes->get($item->movedTo)) {
                    ImportOmimPhenotype::dispatch($item->movedTo);
                }

                $pheno->moved_to_mim_number = $item->movedTo;
            }

            $pheno->save();
        }
    }

    private function getPhenotypes($searchResults)
    {
        $mimNumbers = collect($searchResults)->map(function ($item) {
            return $item->mimNumber;
        })->toArray();
        
        return Phenotype::whereIn('mim_number', $mimNumbers)->get()->keyBy('mim_number');
    }

    private function getMovedToPhenotypes($searchResults)
    {
        $movedToMims = collect($searchResults)
                        ->map(function ($i) {
                            return isset($i->movedTo) ? $i->movedTo : null;
                        })
                        ->filter();

        return Phenotype::whereIn('mim_number', $movedToMims)->get()->keyBy('mim_number');
    }

    private function getLastUpdated()
    {
        return Carbon::parse('2000-01-01');
    }
}
