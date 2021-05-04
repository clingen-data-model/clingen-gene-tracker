<?php

namespace App\Console\Commands;

use App\AppState;
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
    protected $signature = 'omim:check-moved-and-removed {--page-size=100 : Total number of results to get at once}';

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
        $searchResults = $this->getPaginatedSearchResults($omimClient, [], 0, $this->option('page-size'));

        $phenotypes = $this->getPhenotypes($searchResults);
        $moveToPhenotypes = $this->getMovedToPhenotypes($searchResults);

        foreach ($searchResults as $item) {
            $pheno = $phenotypes->get($item->mimNumber);
            $pheno->omim_status = $item->status;

            if (isset($item->movedTo)) {
                $mimNumbers = explode(',', $item->movedTo);
                foreach ($mimNumbers as $mimNum) {
                    if (!$moveToPhenotypes->get($mimNum)) {
                        ImportOmimPhenotype::dispatch($mimNum);
                    }
                }

                $pheno->moved_to_mim_number = $mimNumbers;
            }

            $pheno->save();
        }

        $this->updateLastCheck();
    }

    private function getPaginatedSearchResults($omimClient, $accumulator = [], $start = 0, $limit = 100)
    {
        $results = $omimClient->paginatedSearch(['search' => $this->buildSearchString()], $start, $limit);
        $accumulator = array_merge($accumulator, $results['entries']->toArray());

        if ($results['total'] == $results['end']+1) {
            return $accumulator;
        }

        $newStart = $results['end']+1;
        return $this->getPaginatedSearchResults($omimClient, $accumulator, $newStart, $limit);
    }

    private function buildSearchString()
    {
        $searchParams = ['prefix:%5E'];
        
        $lastUpdated = $this->getLastUpdated();
        if ($lastUpdated) {
            $searchParams[] = 'date_updated:'.$lastUpdated->format('Y/m/d').'-*';
        }

        return implode(' AND ', $searchParams);
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
                            return isset($i->movedTo) ? explode(',', $i->movedTo) : null;
                        })
                        ->filter()
                        ->flatten();

        return Phenotype::whereIn('mim_number', $movedToMims)->get()->keyBy('mim_number');
    }

    private function getLastUpdated()
    {
        return AppState::findByName('last_omim_moved_check')->value;
    }

    private function updateLastCheck()
    {
        AppState::findByName('last_omim_moved_check')->update(['value' => Carbon::now()]);
    }
}
