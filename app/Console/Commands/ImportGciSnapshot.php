<?php

namespace App\Console\Commands;

use App\User;
use Exception;
use App\Curation;
use App\Classification;
use App\CurationStatus;
use App\ModeOfInheritance;
use Carbon\Carbon;
use Illuminate\Console\Command;

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
        $this->curationStatuses = CurationStatus::all()->keyBy('name');
        $this->curationStatuses->put('None', $this->curationStatuses['Uploaded']);
        $this->classifications = Classification::all()->keyBy('name');
        $this->classifications->put('No Reported Evidence', $this->classifications['No Known Disease Relationship']);
        $this->mois = ModeOfInheritance::all()->keyBy('hp_id');

        $fh = fopen($this->argument('file'), 'r');

        $headerKeys = null;
        while ($line = fgetcsv($fh)) {
            if (is_null($headerKeys)) {
                $headerKeys = array_map(function ($item) {
                    return trim(strtolower($item));
                }, $line);
                continue;
            }

            $row = array_combine($headerKeys, $line);

            try {
                $curation = $this->getMatchingCuration($row);
                $this->updateStatus($curation, $row);
                $this->updateClassification($curation, $row);
                $this->updateGdmUUID($curation, $row);
                $this->updateCurator($curation, $row);
                $this->updateMoi($curation, $row);
            } catch (Exception $e) {
                if ($e->getCode() !== 0) {
                    throw $e;
                }
                $this->error($e->getMessage());
            }
        }
    }

    private function getMatchingCuration($row)
    {
        $curation = $this->curations->filter(function ($curation) use ($row) {
            return 'HGNC:'.$curation->hgnc_id == $row['hgnc id']
                    && $curation->mondo_id = 'MONDO:'.str_pad($row['mondo id'], 7, '0', STR_PAD_LEFT);
        })->first();

        if (is_null($curation)) {
            throw new Exception('Curation with HGNC_ID '.$row['hgnc id'].' and MonDO ID '.$row['mondo id'].' could not be found');
        }

        return $curation;
    }

    private function updateStatus($curation, $row)
    {
        if (!isset($this->curationStatuses[$row['status']])) {
            throw new Exception('Unknown status: '.$row['status']);
        }
        $newStatus = $this->curationStatuses[$row['status']];
        if ($curation->currentStatus->id != $newStatus->id) {
            $curation->statuses()
                ->syncWithoutDetaching($newStatus->id);
        }
    }

    private function updateClassification($curation, $row)
    {
        if (!isset($this->classifications[$row['classification']])) {
            throw new Exception('Unknown classification: '.$row['classification']);
        }
        $newClassification = $this->classifications[$row['classification']];
        if ($curation->currentClassification != $newClassification) {
            $curation->classifications()
                ->syncWithoutDetaching($newClassification);
        }
    }

    private function updateGdmUUID($curation, $row)
    {
        $curation->update(['gdm_uuid' => $row['gdm uuid']]);
    }

    private function updateCurator($curation, $row)
    {
        if (empty($row['creator email'])) {
            throw new Exception('no email data for row');
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
            $curation->update([
                'curator_id' => $user->id,
            ]);
        }
    }

    // private function updateAffiliation($curation, $row)
    // {
    // }

    private function updateMoi($curation, $row)
    {
        $moiId = substr(substr($row['moi'], -11), 0, 10);
        $moi = $this->mois->get($moiId);
        $curation->update(['moi_id' => $moi->id]);
    }
}
