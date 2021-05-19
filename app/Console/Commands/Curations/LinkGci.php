<?php

namespace App\Console\Commands\Curations;

use App\Curation;
use App\GciCuration;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use App\Jobs\Curations\LinkGciCuration;
use App\Jobs\ReplayGciEventsForCuration;
use App\Jobs\Curations\LinkToGciCuration;

class LinkGci extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'curations:link-gci';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Links unmatched Curation records with GCI curation records.';

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
        $curations = $this->getLinkableCurations();

        $curations->each(function ($curation) {
            Bus::dispatch(new LinkGciCuration($curation));
        });

        $this->call('curations:order-statuses');
    }

    private function getLinkableCurations()
    {
        return Curation::query()
            ->with('expertPanel', 'expertPanel.affiliation')
            ->whereNotNull('mondo_id')
            ->whereNotNull('moi_id')
            ->whereNull('gdm_uuid');
    }
}
