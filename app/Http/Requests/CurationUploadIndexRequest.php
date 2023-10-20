<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CurationUploadIndexRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'where.curation_id' => [
                'nullable',
            ],
            'where.upload_category_id' => [
                'nullable',
            ],
            'sort.field' => [
                'nullable',
            ],
            'sort.dir' => [
                'nullable',
            ],
            'with' => [
                'nullable',
            ],
            'with_deleted' => [
                'nullable',
            ],
        ];
    }
}
