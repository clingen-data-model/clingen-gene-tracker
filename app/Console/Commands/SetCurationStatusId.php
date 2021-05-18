<?php

namespace App\Console\Commands;

use App\Curation;
use App\CurationStatus;
use Illuminate\Console\Command;
use App\Jobs\Curations\AddStatus;
use Illuminate\Support\Facades\Bus;

class SetCurationStatusId extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'curations:set_current_status_id';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sets curations curation_status_id based on currentStatus';

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
    public function handle()
    {
        $uploadedStatus = CurationStatus::find(config('project.curation-statuses.uploaded'));

        $curations = Curation::all();
        $bar = $this->output->createProgressBar($curations->count());
        $curations->each(function ($curation) use ($uploadedStatus, $bar) {
            $currentStatus = $curation->curationStatuses
                            ->sortByDesc(function ($item) {
                                return $item->pivot->status_date->timestamp.'.'.$item->id;
                            })
                            ->first();
            if (!$currentStatus) {
                $currentStatus = $uploadedStatus;
                Bus::dispatch(new AddStatus($curation, $currentStatus));
            }
            
            $curation->update(['curation_status_id' => $currentStatus->id]);

            $bar->advance();
        });

        $bar->finish();
        echo("\n");
    }
}
