<?php

namespace App\Services;

use App\User;
use App\Rationale;
use App\ExpertPanel;
use App\CurationType;

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

    public function rowIsValid($rowData)
    {
        $valid = true;
        $errors = [];

        if (!is_null($rowData['curator_email']) && !$this->users->pluck('email')->contains($rowData['curator_email'])) {
            $errors['curator_email'] = 'Curator Email '.$rowData['curator_email'].' was not found in the system';
            $valid = false;
        }

        if (is_null($rowData['expert_panel_id'])) {
            $errors['expert_panel_id'] = 'Expert Panel Id is required';
            $valid = false;
        } elseif (!$this->panels->pluck('id')->contains($rowData['expert_panel_id'])) {
            $errors['expert_panel_id'] = 'Expert Panel Id '.$rowData['expert_panel_id'].' was not found in the system';
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
        
        $this->validationErrors->push($rowData);
        return $valid;
    }
}
