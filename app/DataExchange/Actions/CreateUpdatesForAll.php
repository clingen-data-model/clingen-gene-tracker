<?php

namespace App\DataExchange\Actions;

use App\Curation;
use Illuminate\Support\Facades\Bus;
use Lorisleiva\Actions\Concerns\AsCommand;
use App\Jobs\Curations\CreateStreamMessage;
use Illuminate\Console\Command;

class CreateUpdatesForAll
{
    use AsCommand;

    public string $commandSignature = 'dx:create-updates-for-all {--limit= : limit the nummber of curations} {--where= : where conditions} {--onlyCount} {--showQuery}';

    public function getCommandDescription(): string
    {
        return 'Create update messages for all precurations.';
    }
    

    public function handle(Command $command)
    {
        $curationQuery = Curation::query()
                        ->with(['rationales', 'expertPanel', 'affiliation', 'phenotypes', 'curationStatuses', 'currentStatus', 'curationType', 'modeOfInheritance', 'gene', 'disease', 'curator', 'gciCuration']);
        
        if ($command->option('where')) {
            $curationQuery->whereRaw($command->option('where'));
        }

        if ($command->option('limit')) {
            $curationQuery->limit($command->option('limit'));
        }

        if ($command->option('showQuery')) {
            $command->info(renderQuery($curationQuery));
            return;
        }

        if ($command->option('onlyCount')) {
            $command->info('Result Count: '.$curationQuery->count());
            $command->info('Results from query: '.renderQuery($curationQuery));
            return;
        }

        $curations = $curationQuery->get();

        $command->withProgressBar($curations, function ($curation) {
            $job = new CreateStreamMessage(
                        config('dx.topics.outgoing.precuration-events'),
                        $curation,
                        'updated'
                    );
            Bus::dispatch($job);
        });
        echo "\n";

        
    }
    
}
