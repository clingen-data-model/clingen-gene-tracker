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
        $errors = [];
        foreach ($rows as $row) {
            try {
                $curation = $this->getMatchingCuration($row);
                $this->updateGdmUUID($curation, $row);
                $this->updateStatus($curation, $row);
                $this->updateClassification($curation, $row);
                $this->updateCurator($curation, $row);
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
        echo "\n";
        foreach ($errors as $type => $contents) {
            $errors[$type] = array_flatten($contents, 1);
        }
        file_put_contents(base_path('files/gci_snapshot_import_errors.json'), json_encode($errors));
        foreach ($errors as $key => $category) {
            $this->info(count($category).' '.$key.' errors');
        }
        $this->info('Errors written to '.base_path('files/gci_snapshot_import_errors.json'));
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
