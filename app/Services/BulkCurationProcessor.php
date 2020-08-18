<?php

namespace App\Services;

use App\User;
use App\Curation;
use App\Rationale;
use App\ExpertPanel;
use App\CurationType;
use App\CurationStatus;
use App\Clients\OmimClient;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Jobs\Curations\AddStatus;
use App\Rules\ValidHgncGeneSymbol;
use Illuminate\Support\Facades\DB;
use App\Jobs\Curations\SyncPhenotypes;
use GuzzleHttp\Exception\ClientException;
use App\Exceptions\DuplicateBulkCurationException;
use App\Exceptions\BulkUploads\InvalidRowException;
use App\Exceptions\BulkUploads\InvalidFileException;
use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;

class BulkCurationProcessor
{
    public $users;
    public $curationTypes;
    public $rationales;
    public $panels;
    protected $validationErrors;
    protected $omim;
    
    public function __construct()
    {
        $this->users = User::all();
        $this->curationTypes = CurationType::all();
        $this->rationales = Rationale::all();
        $this->panels = ExpertPanel::all();
        $this->validationErrors = collect();
        $this->omim = new OmimClient();

        $this->statuses = CurationStatus::all()->keyBy('id');
    }
    
    public function getValidationErrors()
    {
        return $this->validationErrors;
    }
    

    public function processFile($path, $expertPanelId)
    {
        ini_set('max_execution_time', '360');
        
        DB::beginTransaction();

        $reader = ReaderEntityFactory::createXLSXReader();
        $reader->open($path);
        $newCurations = collect();
        foreach ($reader->getSheetIterator() as $sheet) {
            if ($sheet->getName() === 'Curations') {
                if (!$this->sheetIsValid($sheet)) {
                    DB::rollBack();
                    throw new InvalidFileException($this->validationErrors);
                }

                if ($this->sheetHasDuplicates($sheet)) {
                    DB::rollBack();
                    throw new DuplicateBulkCurationException($this->duplicates);
                }

                $newCurations = $this->handleSheet($sheet, $expertPanelId);
            }
        }

        DB::commit();

        return $newCurations;
    }

    private function sheetHasDuplicates($sheet) {
        $genes = collect();
        $this->applyToSheet($sheet, function ($idx, $data) use ($genes) {
            $genes->push(['gene_symbol' => $data['gene_symbol'], 'row' => $idx]);
        });

        $genes = $genes->groupBy('gene_symbol');

        $duplicates = Curation::select('id', 'gene_symbol', 'hgnc_id', 'expert_panel_id', 'mondo_id', 'mondo_name', 'created_at', 'updated_at')
                                ->with('expertPanel', 'phenotypes', 'curationStatuses')
                                ->whereIn('gene_symbol', $genes->keys())
                                ->get();

        if ($duplicates->count() > 0) {
            $this->duplicates = $duplicates;
            return true;
        }

        return false;
    }

    public function processWithDuplicates($path, $expertPanelId)
    {
        ini_set('max_execution_time', '360');
        
        DB::beginTransaction();

        $reader = ReaderEntityFactory::createXLSXReader();
        $reader->open($path);
        $newCurations = collect();
        foreach ($reader->getSheetIterator() as $sheet) {
            if ($sheet->getName() === 'Curations') {
                $newCurations = $this->handleSheet($sheet, $expertPanelId);
            }
        }

        if (count($this->validationErrors) > 0) {
            DB::rollBack();
            throw new InvalidFileException($this->validationErrors);
        }

        DB::commit();

        return $newCurations;
    }
    

    private function sheetIsValid($sheet)
    {
        $rowCount = 0;
        $this->applyToSheet($sheet, function ($idx, $data) use (&$rowCount) {
            $this->rowIsValid($data, $idx);
            $rowCount++;
        });

        if ($rowCount > 50) {
            $this->validationErrors->put('file', ['Your upload contains '.($rowCount).' curations. At this time the bulk upload is limited to 50 curations.']);
        }

        return ($this->validationErrors->count() == 0);
    }
    
    private function handleSheet($sheet, $expertPanelId)
    {
        $newCurations = collect();
        $header = [];

        $this->applyToSheet($sheet, function ($idx, $data) use ($newCurations, $expertPanelId) {
            try {
                $newCurations->push($this->processRow($data, $expertPanelId, $idx));
            } catch (InvalidRowException $e) {
            }
        });

        return $newCurations;
    }

    private function collateRow($header, $row)
    {
        $values = array_pad(
            array_map([$this, 'emptyStringToNull'], $row->toArray()),
            count($header),
            null
                );

        return array_combine($header, $values);
    }

    private function emptyStringToNull($item)
    {
        return $item == '' ? null : $item;
    }

    private function rowIsEmpty($data)
    {
        return empty($data['gene_symbol']) && empty($data['curator_email']) && empty($data['curation_type']);
    }

    public function processRow($rowData, $expertPanelId, $rowNum = 0)
    {
        config(['app.bulk_uploading' => true]);

        $curator = $this->users->firstWhere('email', $rowData['curator_email']);
        $curationTypes = $this->curationTypes->firstWhere('name', $rowData['curation_type']);

        $attributes = [
            'gene_symbol' => $rowData['gene_symbol'],
            'expert_panel_id' => $expertPanelId,
            'curator_id' => ($curator) ? $curator->id : null,
            'curation_type_id' => ($curationTypes) ? $curationTypes->id : null,
            'mondo_id' => $rowData['mondo_id'],
            'disease_entity_if_there_is_no_mondo_id' => $rowData['disease_entity_if_there_is_no_mondo_id'],
            'rationale_notes' => $rowData['rationale_notes'],
            'pmids' => $this->getPmids($rowData)
        ];
        $curation = Curation::create($attributes);

        SyncPhenotypes::dispatchNow($curation, $this->getPhenotypes($rowData));
        $curation->rationales()->sync($this->getRationales($rowData));
        
        foreach ($this->getStatusNames() as $id => $statusName) {
            $this->addStatus($curation, $this->statuses->get($id), $statusName.'_date', $rowData);
        }

        if (!$curation->fresh()->currentStatus) {
            AddStatus::dispatch($curation, $this->uploadedStatus);
        }

        config(['app.bulk_uploading' => false]);
        return $curation;
    }

    public function getStatusNames()
    {
        return array_map(function($statusName) { 
                return Str::snake(Str::camel($statusName)); 
            },  
            array_flip(config('project.curation-statuses'))
        );
    }

    private function addStatus($curation, $status, $dateName, $row)
    {
        if (isset($row[$dateName])) {
            AddStatus::dispatch($curation, $status, $row[$dateName]);
        }
    }

    private function getPmids($rowData)
    {
        $pmids = [];
        for ($i=0; $i < 10; $i++) {
            if (isset($rowData['pmid_'.$i])) {
                $pmids[] = $rowData['pmid_'.$i];
            }
        }
        return $pmids;
    }

    private function getPhenotypes($rowData)
    {
        $badMimNumbers = [];
        $phenotypes = [];
        for ($i=0; $i < 10; $i++) {
            if (isset($rowData['omim_id_'.$i])) {
                $mimNumber = $rowData['omim_id_'.$i];
                try {
                    $omimData = $this->omim->getEntry($mimNumber);
                    $phenotypes[] = ['mim_number'=>$omimData->mimNumber, 'name'=> $omimData->titles->preferredTitle];
                } catch (ClientException $e) {
                    $badMimNumbers[] = 'Bad mim number at OMIM ID '.$i.': '.$mimNumber;
                }
            }
        }
        if (count($badMimNumbers) > 0) {
            throw new InvalidRowException($rowData, $badMimNumbers);
        }
        return $phenotypes;
    }

    private function getRationales($rowData)
    {
        $rationales = [];
        for ($i=0; $i < 4; $i++) {
            if (isset($rowData['rationale_'.$i])) {
                $rationales[] = $this->rationales->firstWhere('name', $rowData['rationale_'.$i])->id;
            }
        }
        return $rationales;
    }

    public function rowIsValid($rowData, $rowNum = 0)
    {
        $validationRules = [
            'gene_symbol'=> ['required', new ValidHgncGeneSymbol],
            'curator_email' => ['nullable', 'exists:users,email'],
            'curation_type' => ['nullable', Rule::in($this->curationTypes->pluck('name')->toArray())],
        ];
        $messages = [
            'gene_symbol.required' => 'A gene symbol is required to create a curation',
            'curator_email.exists' => 'The curator email specified was not found in the system',
        ];

        $validator = \Validator::make(
            $rowData,
            $validationRules,
            $messages
        );

        $errors = [];
        foreach ($validator->errors()->getMessages() as $key => $value) {
            $errors[$key] = implode(', ', $value);
        }

        $valid = !$validator->fails();

        for ($i=0; $i < 10; $i++) {
            if (isset($rowData['omim_id_'.$i]) && !empty($rowData['omim_id_'.$i])) {
                $mimNumber = $rowData['omim_id_'.$i];
                try {
                    $entry = $this->omim->getEntry($mimNumber);
                    if (count($entry->isValid()) < 1) {
                        $errors['OMIM ID '.$i] = 'Bad mim number: '.$mimNumber;
                        $valid = false;
                    }
                } catch (ClientException $e) {
                    $errors['OMIM ID '.$i] = 'Bad mim number: '.$mimNumber;
                    $valid = false;
                }
            }
        }

        for ($i=1; $i < 5; $i++) {
            $field = 'rationale_'.$i;
            if (!isset($rowData[$field]) || is_null($rowData[$field])) {
                continue;
            }

            if (!$this->rationales->pluck('name')->contains($rowData[$field])) {
                $errors[$field] = 'Rationale '.$rowData[$field].' was not found in the system';
                $valid = false;
            }
        }
    
        if (count($errors) > 0) {
            $this->validationErrors->put($rowNum, $errors);
        }
        return $valid;
    }

    private function applyToSheet($sheet, callable $callable)
    {
        foreach ($sheet->getRowIterator() as $idx => $row) {
            if ($idx == 1) {
                $header = array_map(function ($item) {
                    return implode('_', explode(' ', strtolower($item)));
                }, $row->toArray());
                continue;
            }
                    
            $data = $this->collateRow($header, $row);

            if ($this->rowIsEmpty($data)) {
                continue;
            }
                    
            $callable($idx, $data);
        }

        return $sheet;
    }
}
