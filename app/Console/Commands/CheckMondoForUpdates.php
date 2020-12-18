<?php

namespace App\Console\Commands;

use App\Contracts\MondoClient;
use App\Curation;
use App\Exceptions\HttpNotFoundException;
use App\Jobs\NotifyCoordinatorsAboutCuration;
use App\Notifications\Curations\MondoIdNotFound;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

class CheckMondoForUpdates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'curations:check-mondo-updates 
                                {--mondo-id : specific mondo id to look for}
                                {--print-errors : Print out put to console} 
                                {--print-requests : Print mondoIds being searched and outcome of search }
                            ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks the MonDO API for updates to nomenclature';

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
    public function handle(MondoClient $mondoClient)
    {
        $printRequests = $this->option('print-requests');
        \Log::info('Checking MonDO for updates.');
        $curations = Curation::query()
                        ->with('expertPanel')
                        ->get()
                        ->filter(function ($curation) {
                            return !is_null($curation->mondo_id);
                        });

        $bar = $this->output->createProgressBar($curations->count());

        $curationsByMondoId = $curations->groupBy('numericMondoId');

        foreach ($curationsByMondoId as $mondoId => $curationsForMondo) {
            if ($printRequests) {
                $this->info('looking up '.$mondoId);
            }

            try {
                $mondoRecord = $mondoClient->fetchRecord($mondoId);
                if ($printRequests) {
                    $this->info('  - found '.$mondoId);
                }
                foreach ($curationsForMondo as $curation) {
                    $curation->update(['mondo_name' => $mondoRecord->label]);
                }
            } catch (HttpNotFoundException $th) {
                if ($printRequests) {
                    $this->warn('  - '.$mondoId.' NOT FOUND');
                    continue;
                }
                if ($this->option('print-errors')) {
                    $this->warn($th->getMessage());
                    continue;
                }
                $this->sendNotificationForCurations($curationsForMondo);
            }

            $bar->advance($curationsForMondo->count());
        }
        echo "\n";
    }

    public function sendNotificationForCurations(Collection $curations)
    {
        foreach ($curations as $curation) {
            sendNotificationForCurations::dispatch($curation, MondoIdNotFound::class);
        }
    }
}
