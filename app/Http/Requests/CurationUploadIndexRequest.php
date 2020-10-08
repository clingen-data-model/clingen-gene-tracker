<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CurationUploadIndexRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'where.curation_id' => 'nullable',
            'where.upload_category_id' => 'nullable',
            'sort.field' => 'nullable',
            'sort.dir' => 'nullable',
            'with' => 'nullable',
            'with_deleted' => 'nullable',
        ];
    }
}
