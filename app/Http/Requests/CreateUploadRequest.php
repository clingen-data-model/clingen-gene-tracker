<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateUploadRequest extends FormRequest
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
        $mimes = config('project.supported-mimes');

        return [
            'name' => 'nullable|string|max:255',
            'file' => 'required|file|max:'.getMaxUploadSize().'|mimes:'.implode(',', $mimes),
            'curation_id' => 'required|exists:curations,id',
            'upload_category_id' => 'nullable|exists:upload_categories,id',
            'notes' => 'nullable|max:65535',
        ];
    }
}
