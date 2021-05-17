<?php

namespace App\Console\Commands;

use App\Contracts\OmimClient;
use App\Exceptions\OmimResponseException;
use App\Phenotype;
use Illuminate\Console\Command;

class FixPhenotypeNames extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'curations:fix-pheno-name {--limit= : number of phenos to limit to} {--offset= : start point for query}';

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
    public function handle(OmimClient $omimClient)
    {
        // turn off mail while we do this so we don't flood coordinators with email.
        config(['mail.driver' => 'log']);
    

        $phenoQuery = Phenotype::query();
        if ($this->option('limit')) {
            $phenoQuery->limit($this->option('limit'));
        }
        if ($this->option('offset')) {
            $phenoQuery->offset($this->option('offset'));
        }
        $phenos = $phenoQuery->get();

        $bar = $this->output->createProgressBar($phenos->count());

        $errors = collect();
        $phenos->each(function ($pheno) use ($bar, $omimClient, $errors) {
            $omimEntry = $omimClient->getEntry($pheno->mim_number);
            try {
                if (count($omimEntry->phenotypeMapList) == 0) {
                    return;
                }
                $newName = $omimEntry->phenotypeMapList[0]->phenotypeMap->phenotype;
                
                $pheno->update(['name' => $newName]);
            } catch (OmimResponseException $e) {
                if ($omimEntry->status == 'moved') {
                    return;
                }
                $errors->push($omimEntry);
            } catch (\Exception $e) {
                $errors->push($omimEntry);
            }
            $bar->advance();
        });
        $bar->finish();
        echo"\n";
        if ($errors->count() > 0) {
            dump($errors);
        }
    }
}
