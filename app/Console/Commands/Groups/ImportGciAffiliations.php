<?php

namespace App\Console\Commands\Groups;

use App\Affiliation;
use Illuminate\Console\Command;

class ImportGciAffiliations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'affiliations:import {jsonFile : Path to JSON file with GCI affiliations}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    private $affiliations;

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
        $this->affiliations = Affiliation::all()->keyBy('clingen_id');

        $filePath = $this->argument('jsonFile');
        if (!file_exists($filePath)) {
            $this->error('File at '.$filePath.' does not exist');
        }

        $gciAffiliations = json_decode(file_get_contents($filePath), null, 512, JSON_THROW_ON_ERROR);

        $affiliations = $this->flattenAffiliations($gciAffiliations);

        foreach ($affiliations as $affiliation) {
            $this->addOrUpdateAffiliation($affiliation);
        };
    }

    private function addOrUpdateAffiliation($data)
    {
        if (isset($data['parent_clingen_id'])) {
            $data['parent_id'] = $this->affiliations->get($data['parent_clingen_id'])->id;
            unset($data['parent_clingen_id']);
        }

        if ($this->affiliationExists($data)) {
            $this->info('Affiliation *'.$data['name'].'* with clingen_id '.$data['clingen_id'].' already exists');
            return;
        }

        $affModel = Affiliation::create($data);
        $this->info('Created new affiliation *'.$data['name'].'* with cligen_id '.$data['clingen_id']);
        $this->affiliations->put($affModel->clingen_id, $affModel);
    }
    
    private function affiliationExists($affiliationData)
    {
        return $this->affiliations->keys()->contains($affiliationData['clingen_id']);
    }
    

    private function flattenAffiliations($gciStructure)
    {
        $affiliations = [];
        foreach ($gciStructure as $topLevel) {
            $affiliations[] = [
                'clingen_id' => $topLevel->affiliation_id,
                'name' => $topLevel->affiliation_fullname,
                'affiliation_type_id' => 1
            ];
            if (isset($topLevel->subgroups->vcep)) {
                $affiliations[] = [
                    'clingen_id' => $topLevel->subgroups->vcep->id,
                    'name' => $topLevel->subgroups->vcep->fullname,
                    'parent_clingen_id' => $topLevel->affiliation_id,
                    'affiliation_type_id' => 4
                ];
            }
            if (isset($topLevel->subgroups->gcep)) {
                $affiliations[] = [
                    'clingen_id' => $topLevel->subgroups->gcep->id,
                    'name' => $topLevel->subgroups->gcep->fullname,
                    'parent_clingen_id' => $topLevel->affiliation_id,
                    'affiliation_type_id' => 3
                ];
            }
        }
        return $affiliations;
    }
}
