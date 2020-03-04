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
    private $errorCodes = [
        0 => 'mapping',
        401 => 'no curator',
        404 => 'missing',
        409 => 'ambiguous'
    ];

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

        $this->curations = Curation::with('curator')->get();
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

        $rows = $this->getRows();

        $bar = $this->output->createProgressBar(count($rows));
        $errors = [];
        foreach ($rows as $row) {
            try {
                $curation = $this->getMatchingCuration($row);
                $this->updateGdmUUID($curation, $row);
                $this->updateStatus($curation, $row);
                $this->updateClassification($curation, $row);
                // $this->updateCurator($curation, $row);
                $this->updateMoi($curation, $row);
                $this->setAffiliation($curation, $row);
                if (is_null($curation->mondo_id)) {
                    $curation->mondo_id = $row['mondo id'];
                }
                $curation->save();
            } catch (GciSyncException $e) {
                $type = $this->errorCodes[$e->getCode()];
                if (!isset($errors[$type])) {
                    $errors[$type] = [];
                }
                if ($e->hasData()) {
                    $errors[$type][] = $e->getData();
                } else {
                    $errors[$type][] = $e->getMessage();
                }
            } catch (Exception $e) {
                // dump($row);
                throw $e;
            }
            $bar->advance();
        }

        $this->reportErrors($errors);
    }

    private function getRows()
    {
        $fh = fopen($this->argument('file'), 'r');

        $headerKeys = null;
        $rows = [];
        $skipped = 0;
        while ($line = fgetcsv($fh)) {
            if (is_null($headerKeys)) {
                $headerKeys = array_map(function ($item) {
                    return trim(strtolower($item));
                }, $line);
                continue;
            }

            
            $row = array_combine($headerKeys, $line);
            if ($this->skipRow($row)) {
                $skipped++;
                continue;
            }

            $rows[] = $row;
        }
        $this->info('Skipped curations: '.$skipped);

        return $rows;
    }

    private function reportErrors($errors)
    {
        echo "\n";
        foreach ($errors as $type => $contents) {
            $errors[$type] = array_flatten($contents, 1);
        }
        file_put_contents(base_path('files/gci_snapshot_import_errors.json'), json_encode($errors));
        foreach ($errors as $key => $category) {
            $this->info(count($category).' '.$key.' errors');
        }
        $this->info('Errors written to '.base_path('files/gci_snapshot_import_errors.json'));

        if (isset($errors['missing'])) {
            $fh = fopen(base_path('files/gci_snapshot_missing.csv'), 'w');
            fputcsv($fh, array_keys($errors['missing'][0]));
            foreach ($errors['missing'] as $row) {
                fputcsv($fh, $row);
            }
            fclose($fh);
        }
    }
    
    

    private function skipRow($row)
    {
        $skipData = [
            '6eecf268-ed41-472b-980b-354ccf103259',
            'b38fd6d6-459c-425c-bc00-705a71c2b7f0',
            '9f2593d9-aa90-4051-b9cf-30c3a22a68ea',
            '48f788db-a9ae-4fc0-b1b4-4c1aebdceb3e',
            '0defa473-b0d5-453a-8a45-8b1937a2899f',
            '0defa473-b0d5-453a-8a45-8b1937a2899f',
            '5b1b4732-7b7b-48ae-9ec9-e062a199b9a1',
            '61901d97-88fd-4812-9ab2-18eeaeab53db',
            '8f55a7d5-5fb1-479b-90c9-7a7fbc297322',
            'adaf0fc1-272e-4731-821f-9676d76ba548',
            'd621f224-0f33-4d79-9285-88b5b8f5cbe1',
            'c6622002-6d82-4bc4-94d5-e81822d006f2',
            'c9f413ba-bfbd-4f75-8dd3-eafe869c5cc1',
            '6189bdf4-8751-4ac1-a7d8-b159d3dfb92f',
            '1fcd4927-5557-4d07-84ba-4a1cba6d13f6',
        ];

        return in_array($row['gdm uuid'], $skipData);
    }
    

    private function getMatchingCuration($row)
    {
        $curation = $this->matchHgncAndMondo($row);
        if (is_null($curation)) {
            $curation = $this->matchHgncAndEmail($row);
        }

        if (is_null($curation)) {
            $keys = [
                'gene_symbol' => $row['hgnc gene symbol'],
                'hgnc_id' => $row['hgnc id'],
                'mondo_id' => $row['mondo id'],
                'affiliation' => $row['affiliation name'],
                'createor' => $row['creator email']
            ];
            $hgncMondoData = json_encode(['HGNC_ID' => $row['hgnc id'], 'MonDO ID' => $row['mondo id']]);
            $th = new GciSyncException('Curation not found: '.$hgncMondoData, 404);
            $th->addData($keys);
            throw $th;
        }

        return $curation;
    }

    private function matchHgncAndMondo($row)
    {
        return $this->curations->filter(function ($curation) use ($row) {
            return 'HGNC:'.$curation->hgnc_id == $row['hgnc id']
                    && $curation->mondo_id == 'MONDO:'.str_pad($row['mondo id'], 7, '0', STR_PAD_LEFT);
        })->first();
    }

    private function matchHgncAndEmail($row)
    {
        $matching = $this->curations->filter(function ($curation) use ($row) {
            if (is_null($curation->curator)) {
                return false;
            }
            if (!is_null($curation->mondo_id)) {
                return false;
            }
            return 'HGNC:'.$curation->hgnc_id == $row['hgnc id']
                && $curation->curator->email == $row['creator email'];
        });

        if ($matching->count() > 1) {
            throw $this->buildRowError($row, 409);
        }

        return $matching->first();
    }
    
    private function buildRowError($data, $code)
    {
        $keys = [
            'gene_symbol' => $data['hgnc gene symbol'],
            'hgnc_id' => $data['hgnc id'],
            'mondo_id' => $data['mondo id'],
            'affiliation' => $data['affiliation name'],
            'createor' => $data['creator email']
        ];
        $hgncMondoData = json_encode(['HGNC_ID' => $data['hgnc id'], 'MonDO ID' => $data['mondo id']]);
        $th = new GciSyncException('Curation not found: '.$hgncMondoData, $code);
        $th->addData($keys);

        return $th;
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
        $moi = $this->mois->get($moiId);
        if (!$moi) {
            $moi = $this->mois->firstWhere('name', $row['moi']);
            if (!$moi) {
                throw new GciSyncException('MOI "'.$row['moi'].'" not found');
            }
        }
        $curation->moi_id = $moi->id;
    }
}
