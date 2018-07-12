<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkUploadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return \Auth::user()->isCoordinator() || \Auth::user()->hasAnyRole('programmer|admin');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'expert_panel_id' => 'required|exists:expert_panels,id',
            'bulk_curations' => 'required'
        ];
    }
}
