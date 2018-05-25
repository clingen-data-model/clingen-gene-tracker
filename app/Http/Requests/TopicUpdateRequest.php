<?php

namespace App\Http\Requests;

use App\Clients\OmimClient;

/**
* Request for a topic update request
*/
class TopicUpdateRequest extends TopicCreateRequest
{
    public function rules()
    {
        $rules = parent::rules();
        $rules['page'] = 'required';
        $rules['curation_type_id'] = 'required_if:page,curation-types';
        $rules['rationales'] = 'sometimes';
        $rules['rationale_other'] = 'required_if:rationale_id,100';

        return $rules;
    }

    public function messages()
    {
        $messages = [
            'curation_type_id.required_if' => 'A curation type is required to continue',
            'rationale_ids.required_if' => 'You must select a rationale to continue',

        ];

        return array_merge(parent::messages(), $messages);
    }

    protected function getValidatorInstance()
    {
        $validator = parent::getValidatorInstance();

        // Commented out for now.  keeping for reference when making validation more sophisticated
        $validator->sometimes('rationales', 'required', function ($input) {
            $genePhenos = (new OmimClient())->getGenePhenotypes($input->gene_symbol);
            $test = $input->page == 'phenotypes'
                    && ($genePhenos->count() > 1
                        || ($genePhenos->count() == 1
                            && $input->curation_type_id != 1));
            return $test;
        });

        return $validator;
    }
}
