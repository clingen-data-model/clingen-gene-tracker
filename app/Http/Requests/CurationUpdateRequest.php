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

        return $rules;
    }

    public function messages()
    {
        $messages = [
            'curation_type_id.required' => 'A curation type is required to continue',
            'rationale_ids.required' => 'You must select a rationale to continue',
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
