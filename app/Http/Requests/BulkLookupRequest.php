<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkLookupRequest extends FormRequest
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
            'resource' => ['sometimes','in:simple,full'],
            'gene_symbol' => 'sometimes',
            'classifications' => [],
        ];
    }

    public function messages()
    {
        return [
            'gene_symbol.required' => 'You must include at least one gene symbol to do a bulk lookup.'
        ];
    }
}
