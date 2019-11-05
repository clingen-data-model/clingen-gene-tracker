<?php

namespace App\Console\Commands\OneOff;

use App\Phenotype;
use App\Contracts\OmimClient;
use Illuminate\Console\Command;

class PopulatePhenotypeOmimEntry extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'one-off:populate-omim-entry';

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
    public function handle(OmimClient $omim)
    {
        $phenotypes = Phenotype::select('id', 'mim_number')
                        ->whereNull('omim_entry');
        $bar = $this->output->createProgressBar($phenotypes->count());
        $phenotypes->each(function ($phenotype) use ($omim, $bar) {
            $response = $omim->getEntry($phenotype->mim_number);
            $phenotype->update(['omim_entry' => $response]);
            $bar->advance();
        });
    }
}
