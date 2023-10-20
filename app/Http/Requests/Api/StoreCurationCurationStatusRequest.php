<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreCurationCurationStatusRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'curation_status_id' => [
                'required',
                'exists:curation_statuses,id',
            ],
            'status_date' => [
                'nullable',
                'date_format:Y-m-d',
            ],
        ];
    }
}
