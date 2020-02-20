<?php

namespace App\Console\Commands;

use App\User;
use App\Curation;
use Carbon\Carbon;
use App\Affiliation;
use App\Classification;
use App\CurationStatus;
use App\ModeOfInheritance;
use Illuminate\Console\Command;
use App\Exceptions\GciSyncException;
use Exception;

class ImportGciSnapshot extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gci:snapshot {file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import snapshot of curation data from GCI';

    private $curations;

    private $curationStatuses;

    private $classifications;

    /**
     * Create a new command instance.
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
        config(['mail.driver' => 'array']);
        if (!file_exists($this->argument('file'))) {
            $this->error('File '.$this->argument('file').' does not exist');
        }

        $this->curations = Curation::all();
        $this->curationStatuses = CurationStatus::all()->keyBy(function ($item) {
            return strtolower($item->name);
        });
        $this->curationStatuses->put('none', $this->curationStatuses['uploaded']);
        $this->curationStatuses->put('in progress', $this->curationStatuses['curation in progress']);
        $this->curationStatuses->put('approved', $this->curationStatuses['curation approved']);
        $this->curationStatuses->put('provisional', $this->curationStatuses['curation provisional']);

        $this->classifications = Classification::all()->keyBy('name');
        $this->classifications->put('No Reported Evidence', $this->classifications['No Known Disease Relationship']);
        
        $this->mois = ModeOfInheritance::all()->keyBy('hp_id');
        $this->affiliations = Affiliation::all()->keyBy('clingen_id');

        $fh = fopen($this->argument('file'), 'r');

        $headerKeys = null;
        $rows = [];
        while ($line = fgetcsv($fh)) {
            if (is_null($headerKeys)) {
                $headerKeys = array_map(function ($item) {
                    return trim(strtolower($item));
                }, $line);
                continue;
            }

            $row = array_combine($headerKeys, $line);
            $rows[] = $row;
        }

        $bar = $this->output->createProgressBar(count($rows));
        $errors = [
            0 => [],
            404 =>  []
        ];
        foreach ($rows as $row) {
            try {
                $curation = $this->getMatchingCuration($row);
                $this->updateGdmUUID($curation, $row);
                $this->updateStatus($curation, $row);
                $this->updateClassification($curation, $row);
                $this->updateCurator($curation, $row);
                $this->updateMoi($curation, $row);
                $this->setAffiliation($curation, $row);
                $curation->save();
            } catch (GciSyncException $e) {
                // $this->error($e->getMessage());
                if (!isset($errors[$e->getCode()])) {
                    $errors[$e->getCode()] = [];
                }
                if ($e->hasData()) {
                    $errors[$e->getCode()][] = $e->getData();
                } else {
                    $errors[$e->getCode()][] = $e->getMessage();
                }
            } catch (Exception $e) {
                // dump($row);
                throw $e;
            }
            $bar->advance();
        }
        echo "\n";
        foreach ($errors as $key => $category) {
            $this->info(count($category).' '.$key.' errors');
        }
        // $errors[404] = array_flatten($errors[404], 1);
        // file_put_contents(base_path('files/gci_snapshot_import_errors.json'), json_encode($errors));
    }

    private function getMatchingCuration($row)
    {
        $curation = $this->curations->filter(function ($curation) use ($row) {
            return 'HGNC:'.$curation->hgnc_id == $row['hgnc id']
                    && $curation->mondo_id == 'MONDO:'.str_pad($row['mondo id'], 7, '0', STR_PAD_LEFT);
        })->first();

        if (is_null($curation)) {
            $keys = ['hgnc_id' => $row['hgnc id'], 'mondo_id' => $row['mondo id'], 'affiliation' => $row['affiliation name'], 'createor' => $row['creator email']];
            $th = new GciSyncException('Curation not found: '.json_encode(['HGNC_ID' => $row['hgnc id'], 'MonDO ID' => $row['mondo id']]), 404);
            $th->addData($keys);
            throw $th;
        }

        return $curation;
    }

    private function updateStatus($curation, $row)
    {
        $matchStatus = strtolower($row['status']);
        $newStatus = $this->curationStatuses->get($matchStatus);
        if (!$newStatus) {
            $newStatus = $this->curationStatuses->get('Curation '.$matchStatus);
            if (!$newStatus) {
                throw new GciSyncException('Unknown status: '.$matchStatus);
            }
        }
        if (!$curation->currentStatus || $curation->currentStatus->id != $newStatus->id) {
            $curation->statuses()
                ->syncWithoutDetaching($newStatus->id);
        }
    }

    private function updateClassification($curation, $row)
    {
        if (empty($row['classification']) || $row['classification'] == 'No Classification') {
            return;
        }
        if (!isset($this->classifications[$row['classification']])) {
            throw new GciSyncException('Unknown classification: '.$row['classification']);
        }
        $newClassification = $this->classifications[$row['classification']];
        if ($curation->currentClassification != $newClassification) {
            $curation->classifications()
                ->syncWithoutDetaching($newClassification);
        }
    }

    private function updateGdmUUID($curation, $row)
    {
        $curation->gdm_uuid = $row['gdm uuid'];
    }

    private function updateCurator($curation, $row)
    {
        if (empty($row['creator email'])) {
            throw new GciSyncException('no email data for row');
        }

        $user = User::where('email', $row['creator email'])->first();

        if (is_null($user)) {
            $user = User::create([
                'name' => $row['creator name'],
                'email' => $row['creator email'],
                'password' => uniqid(),
                'deactivated_at' => Carbon::now(),
            ]);
        }

        $user->update(['gci_uuid' => $row['creator uuid']]);

        if (is_null($curation->curator_id)) {
            $curation->curator_id = $user->id;
        }
    }

    private function setAffiliation($curation, $row)
    {
        if (empty($row['affiliation id'])) {
            return;
        }
        if (!isset($this->affiliations[$row['affiliation id']])) {
            throw new GciSyncException('Affiliation id '.$row['affiliation id'].' not found');
        }
        if (is_null($curation->affiliation_id)) {
            $curation->affiliation_id = $this->affiliations[$row['affiliation id']]->id;
        }
    }

    private function updateMoi($curation, $row)
    {
        $moiId = substr(substr($row['moi'], -11), 0, 10);
        if ($moiId == 'HP:0000005') {
            throw new GciSyncException('MOI HP:0000005 is not a valid instance of the MOI class accoding to the HP ontology', 401);
        }
        $moi = $this->mois->get($moiId);
        if (!$moi) {
            throw new GciSyncException('MOI "'.$row['moi'].'" not found');
        }
        $curation->moi_id = $moi->id;
    }
}
