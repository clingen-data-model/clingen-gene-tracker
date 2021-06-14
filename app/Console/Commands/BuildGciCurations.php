<?php

namespace App\Console\Commands;

use Exception;
use Throwable;
use Carbon\Carbon;
use App\Affiliation;
use App\GciCuration;
use App\StreamMessage;
use App\Classification;
use App\CurationStatus;
use App\Gci\GciMessage;
use App\ModeOfInheritance;
use Illuminate\Support\Str;
use InvalidArgumentException;
use App\IncomingStreamMessage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Intervention\Image\Exception\NotFoundException;

class BuildGciCurations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gci:build-curations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Build GciCurations from stream messages.';
    
    protected $mois;
    protected $statuses;
    protected $classifications;
    protected $affiliations;

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
        $this->populateLookups();
        DB::table('gci_curations')->truncate();

        $this->populateFromSnapshot();

        $this->updateFromStreamMessages();
    }

    private function updateFromStreamMessages()
    {
        $this->info('Applying gene_validity_events messages to gci_curations...');
        $streamMessages = IncomingStreamMessage::query();
        $gciCurations = GciCuration::all()->keyBy('gdm_uuid');
        $bar = $this->output->createProgressBar($streamMessages->count());
        $errors = [];
        $streamMessages->chunk(500, function ($chunk) use ($gciCurations, $bar, &$errors) {
            $chunk->each(function ($ism) use ($gciCurations, $bar, &$errors) {
                if (!$ism->gdm_uuid) {
                    return;
                }

                try {
                    $gciMessage = new GciMessage($ism->payload);
                    $newData = [
                        'mondo_id' => $gciMessage->mondoId,
                        'moi_id' => $this->mois->get($gciMessage->getMoi())->id,
                        'classification_id' => $this->getClassificationId($gciMessage->getClassification()),
                        'status_id' => $this->getStatusId($gciMessage->getStatus()),
                        'affiliation_id' => $this->getAffiliationId($gciMessage->getAffiliation()->id),
                        'updated_at' => $gciMessage->getMessageDate(),
                    ];
    
                    $gciCuration = $gciCurations->get($gciMessage->getUuid());
                    if (!$gciCuration || $gciMessage->getStatus() == 'created') {
                        $newData['hgnc_id'] = substr($gciMessage->getHgncId(), 5);
                        $newData['creator_uuid'] = $gciMessage->getCreator()->id;
                        $newData['creator_email'] = $gciMessage->getCreator()->email;
                        $newData['created_at'] = $gciMessage->getMessageDate();
                        $newData['gdm_uuid'] = $gciMessage->getUuid();
                        $gciCuration = GciCuration::firstOrCreate($newData);
                        $gciCurations->put($gciCuration->gdm_uuid, $gciCuration);
                        $bar->advance();
                        return;
                    }
    
                    if (!$gciCuration) {
                        $errors[] = 'GciCuration for gdm_uuid '.$ism->gdm_uuid.' not found. Status: '.$gciMessage->getStatus();
                        $bar->advance();
                        return;
                    }
    
                    $gciCuration->fill($newData);
                    // dd($gciCuration->getDirty());
                    $gciCuration->save();
                    $bar->advance();
                } catch (InvalidArgumentException $e) {
                    $errorsp[] = ($e->getMessage());
                    $bar->advance();
                }
            });
        });
        $bar->finish();
        echo("\n");
        $this->info(count($errors).' errors found.');
        foreach ($errors as $e) {
            $this->error($e);
        }
    }


    private function populateFromSnapshot()
    {
        $this->info('Populating baseline from snapshot...');
        $rows = $this->getRows();
        $bar = $this->output->createProgressBar(count($rows));

        $gdmCount = [];
        foreach ($rows as $row) {
            $data = [
                'hgnc_id' => substr($row['hgnc_id'], 5),
                'mondo_id' => 'MONDO:'.$row['mondo_id'],
                'moi_id' => $this->getMoiId($row['moi']),
                'classification_id' => $this->getClassificationId($row['classification']),
                'status_id' => $this->getStatusId($row['status']),
                'creator_uuid' => $row['creator_uuid'],
                'creator_email' => $row['creator_email'],
                'affiliation_id' => $this->getAffiliationId($row['affiliation_id']),
                'created_at' => Carbon::parse($row['gdm_created_date']),
                'updated_at' => Carbon::parse($row['gdm_created_date'])
            ];
            if (!isset($gdmCount[$row['gdm_uuid']])) {
                $gdmCount[$row['gdm_uuid']] = [];
            }
            $gdmCount[$row['gdm_uuid']][] =  $data;

            GciCuration::updateOrCreate(
                ['gdm_uuid' => $row['gdm_uuid']],
                $data
            );
            $bar->advance();
        }

        $bar->finish();
        echo("\n");

        $this->info('Created '.GciCuration::count().' GciCuration records');
        $duplicates = collect($gdmCount)->filter(function ($i) {
            return count($i) > 1;
        });
        $this->info('Duplicate records: '.$duplicates->count().':');
    }

    private function getRows()
    {
        $snapshotPath = base_path('files/gci_snapshot.csv');
        if (!file_exists($snapshotPath)) {
            throw new Exception('Snapshot file not found at '.$snapshotPath);
        }

        $fh = fopen($snapshotPath, 'r');

        $headerKeys = null;
        $rows = [];
        $skipped = 0;
        while ($line = fgetcsv($fh)) {
            if (is_null($headerKeys)) {
                $headerKeys = array_map(function ($item) {
                    return trim(Str::snake(strtolower($item)));
                }, $line);
                continue;
            }

            
            $row = array_combine($headerKeys, $line);
            // if ($this->skipRow($row)) {
            //     $skipped++;
            //     continue;
            // }

            $rows[] = $row;
        }
        $this->info('Skipped curations: '.$skipped);

        return $rows;
    }

    private function getStatusId($name)
    {
        $name = str_replace('_', ' ', strtolower($name));
        if (!$this->statuses->get($name)) {
            throw new InvalidArgumentException($name.' not found in statuses table');
        }
        return $this->statuses->get($name)->id;
    }
    
    private function getClassificationId($name)
    {
        $name = str_replace('_', ' ', strtolower($name));
        if (empty($name) || $name == 'no classification') {
            return null;
        }
        if (!$this->classifications->get($name)) {
            throw new InvalidArgumentException($name.' not found in classifications table');
        }
        return $this->classifications->get($name)->id;
    }
    
    private function getAffiliationId($affiliationId)
    {
        if (empty($affiliationId)) {
            return null;
        }
        if (!$this->affiliations->get($affiliationId)) {
            throw new InvalidArgumentException($affiliationId.' not found in affiliations table');
        }
        return $this->affiliations->get($affiliationId)->id;
    }

    private function getMoiId($moiString)
    {
        return $this->mois->get($this->parseMoi($moiString))->id;
    }

    private function parseMoi($moiString)
    {
        $matches = [];
        preg_match('/\w*\((HP:\d{7})\)$/', $moiString, $matches);
        if (count($matches) != 2) {
            if ($moiString == 'Other') {
                return 'Other';
            }
            throw new InvalidArgumentException('Failed to parse MOI string '.$moiString);
        }
        return $matches[1];
    }

    private function populateLookups()
    {
        $this->mois = ModeOfInheritance::all()->keyBy('hp_id');
        $this->mois->put('Other', $this->mois['HP:0000000']);
        $this->mois->put('other', $this->mois['HP:0000000']);

        $this->statuses = CurationStatus::all()
                            ->map(function ($st) {
                                $st->name = strtolower($st->name);
                                return $st;
                            })
                            ->keyBy('name');
                            
        $this->statuses->put('none', $this->statuses['uploaded']);
        $this->statuses->put('in progress', $this->statuses['precuration complete']);
        $this->statuses->put('created', $this->statuses['precuration complete']);
        $this->statuses->put('approved', $this->statuses['curation approved']);
        $this->statuses->put('provisional', $this->statuses['curation provisional']);
        $this->statuses->put('provisionally approved', $this->statuses['curation provisional']);
        $this->statuses->put('unpublished', $this->statuses['precuration complete']);

        $this->classifications = Classification::all()
                                    ->map(function ($cl) {
                                        $cl->name = strtolower($cl->name);
                                        return $cl;
                                    })
                                    ->keyBy('name');
        $this->classifications->put('no reported evidence', $this->classifications['no known disease relationship']);

        $this->affiliations = Affiliation::all()->keyBy('clingen_id');
    }
}
