<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TopicCreateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return \Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'gene_symbol'=>'required',
            'expert_panel_id' => 'required',
        ];
    }

    public function messages()
    {
        return [
            'rationale_other.required_if' => 'Please provide details about your rational'
        ];
    }
}
