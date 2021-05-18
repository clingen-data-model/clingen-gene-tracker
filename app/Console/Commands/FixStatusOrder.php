<?php

namespace App\Console\Commands;

use App\Curation;
use App\CurationStatus;
use Illuminate\Console\Command;
use App\Jobs\Curations\UpdateCurrentStatus;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Output\OutputInterface;

class FixStatusOrder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'curations:order-statuses';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    protected $verbosity = OutputInterface::VERBOSITY_NORMAL;

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
        $this->verbosity = $this->getOutput()->getVerbosity();
        $this->curationStatuses = CurationStatus::select('id')->get()->sortBy('id');

        $curations = $this->getCurations();

        $curations->each(function ($c) {
            $this->output('curation '.$c->id.': '.$c->statuses->count().' statuses.');
            $this->reOrderStatuses($c);
        });
    }

    private function reorderStatuses($curation)
    {
        $this->output('reording statuses for curation '.$curation->id);

        $minInstance = $curation->statuses
            ->sortBy('pivot.status_date')
            ->first();

        if ($curation->created_at->timestamp > $minInstance->pivot->status_date->timestamp) {
            $this->output('setting uploaded status date to '.$minInstance->pivot->status_date);
            $curation->statuses()->updateExistingPivot(config('curations.statuses.uploaded'), ['status_date' => $minInstance->pivot->status_date]);
        } else {
            if ($minInstance->curation_status_id != config('curations.statuses.uploaded')) {
                $this->output('setting uploaded status date to '.$curation->created_at);
                $curation->statuses()->updateExistingPivot(config('curations.statuses.uploaded'), ['status_date' => $curation->created_at]);
            }
        }

        UpdateCurrentStatus::dispatch($curation);
    }
    

    private function getCurations()
    {
        return Curation::query()
                ->with('statuses', 'currentStatus')
                ->where('curation_status_id', 1)
                ->whereHas('statuses', function ($q) {
                    $q->where('curation_status_id', '>', 1);
                });
    }


    private function output($message, $output = 'info')
    {
        if ($this->verbosity >= OutputInterface::VERBOSITY_VERBOSE) {
            $this->{$output}($message);
        }
    }
}
