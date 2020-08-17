<?php

namespace App\Console\Commands;

use App\Phenotype;
use App\Contracts\OmimClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Jobs\Curations\UpdateOmimData;

class CheckOmimUpdates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'curations:check-omim-updates {--limit= : Number of phenotypes for which to get updates} {--mim-number= : specific mim-number to check.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks OMIM for nomenclature changes on phenotypes';

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
        Log::info('Checking OMIM for updates');
        $phenotypeQuery = Phenotype::with('curations')
            ->whereHas('curations');

        if ($this->option('limit')) {
            $phenotypeQuery->limit($this->option('limit'));
        }

        if ($this->option('mim-number')) {
            $phenotypeQuery->where('mim_number', trim($this->option('mim-number')));
        }

        $phenotypes = $phenotypeQuery->get();

        $bar = $this->output->createProgressBar($phenotypes->count());
        $phenotypes->each(function ($phenotype) use ($bar) {
            UpdateOmimData::dispatch($phenotype);
            $bar->advance();
        });
    }
}
