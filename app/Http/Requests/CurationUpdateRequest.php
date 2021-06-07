<?php

namespace App\Http\Requests;

use App\Clients\OmimClient;
use App\Rules\ValidOmimId;

/**
* Request for a curation update request
*/
class CurationUpdateRequest extends CurationCreateRequest
{
    public function rules()
    {
        $rules = parent::rules();
        $rules['page'] = 'required';
        $rules['curation_type_id'] = 'sometimes';
        $rules['rationales'] = 'sometimes';
        $rules['rationale_other'] = 'sometimes';
        $rules['isolated_phenotype'] = 'sometimes';
        $rules['mondo_id'] = 'sometimes';

        return $rules;
    }

    public function all($keys = null)
    {
        $data = parent::all($keys);
        if (isset($data['pmids']) && is_string($data['pmids'])) {
            $data['pmids'] = array_map(function ($i) {
                return trim($i);
            }, explode(',', $data['pmids']));
        }
        return $data;
    }

    public function messages()
    {
        $messages = [
            'curation_type_id.required' => 'A curation type is required to continue',
            'rationale_ids.required' => 'You must select a rationale to continue',
            'mondo_id.regex' => 'MonDO ID must have the format "MONDO:1234567"',
            'moi_id' => 'nullable|exists:mode_of_inheritances,id'
        ];

        return array_merge(parent::messages(), $messages);
    }

    protected function getValidatorInstance()
    {
        $validator = parent::getValidatorInstance();

        // Curation Type
        $validator->sometimes('curation_type_id', 'required', function ($input) {
            if (! $this->shouldValidate($input)) {
                return false;
            }
            return $input->page == 'curation-types';
        });

        // Rationale Other
        $validator->sometimes('rationale_other', 'required', function ($input) {
            if (! $this->shouldValidate($input)) {
                return false;
            }
            return $input->rationale_id == 100;
        });

        // Rationales
        $validator->sometimes('rationales', 'required', function ($input) {
            if (! $this->shouldValidate($input)) {
                return false;
            }
            $omim = resolve(OmimClient::class);

            $genePhenos = $omim->getGenePhenotypes($input->gene_symbol);
            if ($input->page == 'phenotypes') {
                if ($genePhenos->count() > 1) {
                    return true;
                }
                if ($genePhenos->count() == 1 && $input->curation_type_id != 1) {
                    return true;
                }
            }
            return false;
        });

        // Isolated Phenotype
        $validator->sometimes('isolated_phenotype', ['required', new ValidOmimId], function ($input) {
            if (! $this->shouldValidate($input)) {
                return false;
            }
            return $input->page == 'phenotypes'
                    && $input->curation_type_id == 3;
        });

        //Mondo ID
        $validator->sometimes('mondo_id', ['nullable', 'regex:/MONDO:\d\d\d\d\d\d\d/i'], function ($input) {
            if (! $this->shouldValidate($input)) {
                return false;
            }

            return $input->page == 'mondo';
        });

        return $validator;
    }

    private function shouldValidate($input)
    {
        if ($input->nav == 'next' || $input->nav == 'finish') {
            return true;
        }
        return false;
    }
}
