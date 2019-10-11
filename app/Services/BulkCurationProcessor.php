<?php

namespace App\Services;

use App\User;
use App\Curation;
use App\Rationale;
use App\ExpertPanel;
use App\CurationType;
use App\Clients\OmimClient;
use Illuminate\Validation\Rule;
use App\Rules\ValidHgncGeneSymbol;
use Illuminate\Support\Facades\DB;
use App\Jobs\Curations\SyncPhenotypes;
use GuzzleHttp\Exception\ClientException;
use App\Exceptions\BulkUploads\InvalidRowException;
use App\Exceptions\BulkUploads\InvalidFileException;
use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;

class BulkCurationProcessor
{
    public $users;
    public $curationTypes;
    public $rationales;
    public $panels;
    protected $omim;
    
    public function __construct()
    {
        $this->users = User::all();
        $this->curationTypes = CurationType::all();
        $this->rationales = Rationale::all();
        $this->panels = ExpertPanel::all();
        $this->validationErrors = collect();
        $this->omim = new OmimClient();
    }
    
    public function processFile($path, $expertPanelId)
    {
        $newCurations = collect();
        DB::beginTransaction();

        $reader = ReaderEntityFactory::createXLSXReader();
        $reader->open($path);
        foreach ($reader->getSheetIterator() as $sheet) {
            if ($sheet->getName() === 'Curations') {
                $header = [];
                foreach ($sheet->getRowIterator() as $idx => $row) {
                    if ($idx == 1) {
                        $header = array_map(function ($item) {
                            return implode('_', explode(' ', strtolower($item)));
                        }, $row->toArray());
                        continue;
                    }
                    $values = array_map(function ($item) {
                        return $item == '' ? null : $item;
                    }, $row->toArray());
                    $data = array_combine($header, $values);
                    if (empty($data['gene_symbol']) && empty($data['curator_email']) && empty($data['curation_type'])) {
                        continue;
                    }
                    try {
                        $newCurations->push($this->processRow($data, $expertPanelId, $idx));
                    } catch (InvalidRowException $e) {
                    }
                }
            }
        }

        // Excel::selectSheets('Curations')->load($path, function ($reader) use ($expertPanelId, $newCurations) {
        //     $rows = $reader->get();
        //     foreach ($rows as $idx => $row) {
        //         if (empty($row->gene_symbol) && empty($row->curator_email) && empty($row->curation_type)) {
        //             continue;
        //         }
        //         try {
        //             $newCurations->push($this->processRow($row->toArray(), $expertPanelId, $idx));
        //         } catch (InvalidRowException $e) {
        //         }
        //     }
        // });

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

        \Bus::dispatch(new SyncPhenotypes($curation, $this->getPhenotypes($rowData)));
        $curation->rationales()->sync($this->getRationales($rowData));
        
        if (isset($rowData['uploaded_date'])) {
            $this->addStatus($curation, 1, $rowData['uploaded_date']);
        }
        if (isset($rowData['precuration_date'])) {
            $this->addStatus($curation, 2, $rowData['precuration_date']);
        }
        if (isset($rowData['disease_entity_assigned_date'])) {
            $this->addStatus($curation, 3, $rowData['disease_entity_assigned_date']);
        }
        if (isset($rowData['curation_in_progress_date'])) {
            $this->addStatus($curation, 4, $rowData['curation_in_progress_date']);
        }
        if (isset($rowData['curation_provisional_date'])) {
            $this->addStatus($curation, 5, $rowData['curation_provisional_date']);
        }
        if (isset($rowData['curation_approved_date'])) {
            $this->addStatus($curation, 6, $rowData['curation_approved_date']);
        }

        config(['app.bulk_uploading' => false]);
        return $curation;
    }

    private function addStatus($curation, $status_id, $date)
    {
        $curation->curationStatuses()->attach([$status_id => ['created_at' => $date]]);
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
                    $omimData = $this->omim->getEntry($mimNumber)[0]->entry;
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
                    $this->omim->getEntry($mimNumber)[0]->entry;
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
}
