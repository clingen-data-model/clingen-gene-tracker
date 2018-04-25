<?php

namespace App\Http\Requests;

/**
* Request for a topic update request
*/
class TopicUpdateRequest extends TopicCreateRequest
{
    public function rules()
    {
        $rules = parent::rules();
        $rules['curation_type_id'] = 'required_if:addingCurationType,1';

        return $rules;
    }

    public function messages()
    {
        $messages = [
            'curation_type_id.required_if' => 'A curation type is required to continue',
            'curation_type_id.exists' => 'The curation type you specified does not exist'
        ];

        return array_merge(parent::messages(), $messages);
    }
}
