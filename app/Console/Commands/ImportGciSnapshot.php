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

    private $uuidMatches;

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
        $this->uuidMatches = collect();

        config(['mail.driver' => 'array']);
        if (!file_exists($this->argument('file'))) {
            $this->error('File '.$this->argument('file').' does not exist');
        }

        $this->disambiguateRecords();

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

        // $dupUuid = collect($rows)
        //     ->sortBy('gdm uuid')
        //     ->groupBy('gdm uuid')
        //     ->filter(function ($grp) {
        //         return $grp->count() > 1;
        //     })
        //     ->map(function ($grp) {
        //         return $grp->map(function ($row) {
        //             return ['status' => $row['status'], 'classification' => $row['classification']];
        //         });
        //     });

        // dd($dupUuid);

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

        $this->info('Found '.$this->uuidMatches->count().' records by uuid');
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
            $errors['missing'] = array_filter($errors['missing'], function ($item) {
                return !empty($item['affiliation']);
            });
        }
        file_put_contents(base_path('files/gci_snapshot_import_errors.json'), json_encode($errors, JSON_PRETTY_PRINT));
        foreach ($errors as $key => $category) {
            $this->info(count($category).' '.$key.' errors');
        }
        $this->info('Errors written to '.base_path('files/gci_snapshot_import_errors.json'));

        if (isset($errors['missing'])) {
            $this->errorsToCsv($errors['missing'], base_path('files/gci_snapshot_missing.csv'));
        }
        if (isset($errors['ambiguous'])) {
            $this->errorsToCsv($errors['ambiguous'], base_path('files/gci_snapshot_ambiguous.csv'));
        }
    }
    
    private function errorsToCsv($errors, $outputFile)
    {
        $fh = fopen($outputFile, 'w');
        fputcsv($fh, array_keys($errors[0]));
        foreach ($errors as $row) {
            fputcsv($fh, $row);
        }
        fclose($fh);
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
            '54d978f1-1ad9-4301-bae0-633923d36110',
            "7cd56c3b-b370-4f9b-9e19-75b290148638",
            'c4031490-5407-4bfc-8c59-4e4fbc47f598',
            'eeb94ab6-1e2e-4f89-89ed-037e6b3a97a6',
            'a9c50f8f-33a0-4c13-87d3-5997849f6993',
        ];

        return in_array($row['gdm uuid'], $skipData);
    }
    

    private function getMatchingCuration($row)
    {
        $curation = $this->matchGdmUuid($row);
        if (!is_null($curation)) {
            $this->uuidMatches->push([$curation->id, $curation->gdm_uuid]);
            return $curation;
        }

        $curation  = $this->matchHgncAndMondo($row);
        if (!is_null($curation)) {
            return $curation;
        }

        // Assemble and throw the not found exception.
        $keys = [
            'gene_symbol' => $row['hgnc gene symbol'],
            'hgnc_id' => $row['hgnc id'],
            'mondo_id' => $row['mondo id'],
            'affiliation' => $row['affiliation name'],
            'creator' => $row['creator email'],
            'uuid' => $row['gdm uuid']
        ];
        $hgncMondoData = json_encode(['HGNC_ID' => $row['hgnc id'], 'MonDO ID' => $row['mondo id']]);
        $th = new GciSyncException('Curation not found: '.$hgncMondoData, 404);
        $th->addData($keys);
        throw $th;
    }

    private function matchGdmUuid($row)
    {
        $uuidMap = $this->curations->keyBy('gdm_uuid');

        return $uuidMap->get($row['gdm uuid']);
    }
    

    private function matchHgncAndMondo($row)
    {
        $curations = $this->curations->filter(function ($curation) use ($row) {
            return 'HGNC:'.$curation->hgnc_id == $row['hgnc id']
                    && $curation->mondo_id == 'MONDO:'.str_pad($row['mondo id'], 7, '0', STR_PAD_LEFT);
        });
        
        if ($curations->count() > 1) {
            throw $this->buildRowError($row, 409);
        }

        return $curations->first();
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
            'createor' => $data['creator email'],
            'uuid' => $data['gdm uuid'],
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

    private function disambiguateRecords()
    {
        $map = [
            1424 => "4fd43e6c-1015-4460-a279-4629ac82259a",
            1532 => "ba9039b2-d139-472d-90b9-fef79f9f532e",
            1533 => "b70a32eb-5863-4859-bb1b-0696dd4d7a3f",
            1535 => "fe512442-fa12-457b-b40d-33a4b863ed04",
            3945 => "f8ccd4e5-8a92-41bf-ae08-2eda3ee42c84",
            100 => "f8a3ab80-4c02-4f66-bfe8-c1d07ecd801d",
            377 => "ea69f940-a536-40b5-9f13-689db9f71126",
            2251 => "499b3d31-14aa-4ff2-bfb6-6e806986255b",
            1517 => "f41b3abf-f562-45e4-89dc-f79a104c4a03",
            1518 => "548a311c-212b-49d0-bf60-44e7e14aa965",
            1524 => "1adc4355-939a-4756-b1b5-d9e6742e49ad",
            1525 => "2328aa25-6230-4b81-9465-18602b4558e5",
            1514 => "2b01bea1-5b69-4878-bbb5-ccb3db7eef5d",
            1515 => "93f4d17d-1e74-43b3-9886-d079542f2ce6",
            1241 => "9fca0130-ec4f-4f0a-9270-3825bb1ee063",
            227 => "8ad2a522-5c68-480b-b37e-7d3d228671ba",
            1543 => "b28b005e-2933-438e-ba1b-26866b4891bd",
            1306 => "b2d6488b-5314-44ae-87e3-48cd156fa8d6",
            1307 => "dae1797c-30f1-4c11-9c11-d91cb5c86504",
            1308 => "f1f5438f-acd0-4ed8-a92c-5bb58e544eff",
            1309 => "bbdfdb48-b677-4076-a3c0-9ee2159c93fc",
            1310 => "fc9cf504-7428-4383-8ea2-1aa26f50eba2",
            1311 => "d4ab608c-95a9-4bf8-9c1c-e1a87b504e08",
            1312 => "36fcfaf7-788e-4f89-aaf1-ddedb5a7a271",
            1313 => "420bcb82-ee9a-4d15-a5bb-fb7190422941",
            1314 => "2aa25fd9-4a0f-438f-baf5-33a0ea72345f",
            1315 => "f271f109-e6a1-4bb0-8cb2-db9e7d9924be",
            2256 => "316ac2fc-555e-4c2e-a0ba-29a7e1663f72",
            1492 => "4ccfba39-0769-4346-ab63-56c9cbd20185",
            1493 => "2ae22ff7-9c17-489a-a723-86a9d7b3eeb0",
            2254 => "61ad3529-b9e2-4b94-a388-3c11b1cb5144"
        ];

        $this->info('Setting uuid on records to disambiguate...');
        $bar = $this->output->createProgressBar(count($map));
        foreach ($map as $curationId => $uuid) {
            Curation::find($curationId)->update(['gdm_uuid' => $uuid]);
            $bar->advance();
        }
        $bar->finish();
        echo "\n";
    }
}
