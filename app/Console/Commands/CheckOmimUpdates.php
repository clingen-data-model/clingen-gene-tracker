<?php

namespace App\Console\Commands;

use App\Phenotype;
use App\Contracts\OmimClient;
use Illuminate\Console\Command;
use App\Jobs\Curations\UpdateOmimData;

class CheckOmimUpdates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'curations:check-omim-updates';

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
        $phenotypes = Phenotype::with('curations')
            ->get();

        $bar = $this->output->createProgressBar($phenotypes->count());
        $phenotypes->each(function ($phenotype) use ($bar){
            UpdateOmimData::dispatch($phenotype);
            $bar->advance();
        });
    }
}
