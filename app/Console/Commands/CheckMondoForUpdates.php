<?php

namespace App\Console\Commands;

use App\Curation;
use App\Contracts\MondoClient;
use App\Exceptions\HttpNotFoundException;
use App\Jobs\Curations\AugmentWithMondoInfo;
use Illuminate\Console\Command;
use App\Mail\Curations\MondoIdNotFound;
use App\Jobs\SendCurationMailToCoordinators;

class CheckMondoForUpdates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'curations:check-mondo-updates';

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
        \Log::info('Checking MonDO for updates.');
        $curations = Curation::query()
                        ->with('expertPanel')
                        ->get()
                        ->filter(function($curation) {
                            return !is_null($curation->mondo_id);
                        });
        $bar = $this->output->createProgressBar($curations->count());
        $curationsByMondoId = $curations->groupBy('numericMondoId');
        
        foreach ($curationsByMondoId as $mondoId => $curationsForMondo) {
            try {
                $mondoRecord = $mondoClient->fetchRecord($mondoId);
                foreach ($curationsForMondo as $curation) {
                    $curation->update(['mondo_name' => $mondoRecord->label]);
                }
            } catch (HttpNotFoundException $th) {
                foreach ($curationsForMondo as $curation) {
                    SendCurationMailToCoordinators::dispatch($curation, MondoIdNotFound::class);
                }
            }
            $bar->advance($curationsForMondo->count());
        }
        echo "\n";
    }
}
