<?php

namespace App\Services;

use App\User;
use App\Curation;
use App\Rationale;
use App\ExpertPanel;
use App\CurationType;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Jobs\Curations\SyncPhenotypes;
use Illuminate\Support\Facades\Validator;
use App\Exceptions\BulkUploads\InvalidRowException;
use App\Exceptions\BulkUploads\InvalidFileException;
use Doctrine\Common\Proxy\Exception\InvalidArgumentException;

class BulkCurationProcessor
{
    public $users;
    public $curationTypes;
    public $rationales;
    public $panels;
    
    public function __construct()
    {
        $this->users = User::all();
        $this->curationTypes = CurationType::all();
        $this->rationales = Rationale::all();
        $this->panels = ExpertPanel::all();
        $this->validationErrors = collect();
    }
    
    public function processFile($path, $expertPanelId)
    {
        $newCurations = collect();
        DB::beginTransaction();
        Excel::selectSheets('Curations')->load($path, function ($reader) use ($expertPanelId, $newCurations) {
            $rows = $reader->get();
            foreach ($rows as $idx => $row) {
                if (empty($row->gene_symbol) && empty($row->curator_email) && empty($row->curation_type)) {
                    continue;
                }
                try {
                    $newCurations->push($this->processRow($row->toArray(), $expertPanelId, $idx));
                } catch (InvalidRowException $e) {
                }
            }
        });
        if (count($this->validationErrors) > 0) {
            DB::rollBack();
            throw new InvalidFileException($this->validationErrors);
        }
        DB::commit();

        return $newCurations;
    }
    
    public function processRow($rowData, $expertPanelId, $rowNum = 0)
    {
        config(['app.bulk_uploading' => true]);
        if (!$this->rowIsValid($rowData, $rowNum)) {
            throw new InvalidRowException($rowData, $this->validationErrors);
        }

        $attributes = [
            'gene_symbol' => $rowData['gene_symbol'],
            'expert_panel_id' => $expertPanelId,
            'curator_id' => $this->users->firstWhere('email', $rowData['curator_email'])->id,
            'curation_type_id' => $this->curationTypes->firstWhere('name', $rowData['curation_type'])->id,
            'mondo_id' => $rowData['mondo_id'],
            'disease_entity_if_there_is_no_mondo_id' => $rowData['disease_entity_if_there_is_no_mondo_id'],
            'rationale_notes' => $rowData['rationale_notes'],
        ];
        $curation = Curation::create($attributes);
        $phenotypes = [];
        for ($i=0; $i < 10; $i++) {
            if (isset($rowData['omim_id_'.$i])) {
                $phenotypes[] = $rowData['omim_id_'.$i];
            }
        }
        \Bus::dispatch(new SyncPhenotypes($curation, $phenotypes));

        $rationales = [];
        for ($i=0; $i < 4; $i++) {
            if (isset($rowData['rationale_'.$i])) {
                $rationales[] = $this->rationales->firstWhere('name', $rowData['rationale_'.$i])->id;
            }
        }
        $curation->rationales()->sync($rationales);
        
        $this->addStatus($curation, 1, $rowData['date_uploaded']);
        $this->addStatus($curation, 2, $rowData['precuration_date']);
        $this->addStatus($curation, 3, $rowData['disease_entity_assigned_date']);
        $this->addStatus($curation, 4, $rowData['curation_in_progress_date']);
        $this->addStatus($curation, 5, $rowData['curation_provisional_date']);
        $this->addStatus($curation, 6, $rowData['curation_approved_date']);

        config(['app.bulk_uploading' => false]);
        return $curation;
    }

    private function addStatus($curation, $status_id, $date)
    {
        $curation->curationStatuses()->attach([$status_id => ['created_at' => $date]]);
    }

    public function rowIsValid($rowData, $rowNum = 0)
    {
        $valid = true;
        $errors = [];

        if (empty($rowData['gene_symbol'])) {
            $errors['gene_symbol'] = 'A gene symbol is required to create a curation';
            $valid = false;
        }

        if (!is_null($rowData['curator_email']) && !$this->users->pluck('email')->contains($rowData['curator_email'])) {
            $errors['curator_email'] = 'Curator Email '.$rowData['curator_email'].' was not found in the system';
            $valid = false;
        }

        if (!is_null($rowData['curation_type']) && !$this->curationTypes->pluck('name')->contains($rowData['curation_type'])) {
            $errors['curation_type'] = 'Curation type '.$rowData['curation_type'].' was not found in the system';
            $valid = false;
        }

        for ($i=1; $i < 5; $i++) {
            $field = 'rationale_'.$i;
            if (!isset($rowData[$field]) || is_null($rowData[$field])) {
                continue;
            }

            if (!$this->rationales->pluck('name')->contains($rowData[$field])) {
                $errors[$field] = 'Curation type '.$rowData[$field].' was not found in the system';
                $valid = false;
            }
        }
    
        if (count($errors) > 0) {
            $this->validationErrors->put($rowNum, $errors);
        }
        return $valid;
    }
}
