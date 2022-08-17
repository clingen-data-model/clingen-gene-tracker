<?php

namespace App\Actions;

use App\Curation;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use App\Jobs\Curations\SyncPhenotypes;
use Lorisleiva\Actions\Concerns\AsCommand;

class CurationPhenotypesPopulateExcluded
{
    use AsCommand;

    public $commandSignature = 'data:populate-excluded-phenotypes {--limit= : Number of curations to update}';

    public function handle(Collection $curations, $progressBar = null)
    {
        
        foreach ($curations as $curation) {
            (new SyncPhenotypes($curation, $curation->phenotypes))->handle();
            if ($progressBar) {
                $progressBar->advance();
            }
        }
    }

    public function asCommand(Command $command)
    {
        $limit = $command->option('limit');

        $curationQuery = Curation::query()
            ->select(['id', 'hgnc_id', 'hgnc_name'])
            ->with(['gene', 'phenotypes', 'gene.phenotypes'])
            ->whereHas('phenotypes', function ($q) {
                $q->where('selected', 1);
            })
            ->whereDoesntHave('phenotypes', function ($q) {
                $q->where('selected', 0);
            });

        $count = $curationQuery->count();
        if ($limit) {
            $curationQuery->limit($limit);
            $count = $limit;
        }

        $bar = $command->getOutput()->createProgressBar($count);
        $this->handle($curationQuery->get(), $bar);
        $bar->finish();
        echo "\n";
    }
    
    
}