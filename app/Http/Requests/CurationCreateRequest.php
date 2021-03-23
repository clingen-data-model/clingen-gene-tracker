<?php

namespace App\Http\Requests;

use App\Rules\ValidGeneSymbolRule;
use Illuminate\Foundation\Http\FormRequest;
use App\Rules\ValidHgncGeneSymbol;

class CurationCreateRequest extends FormRequest
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

        $symbolRule = app()->make(ValidGeneSymbolRule::class);

        return [
            'gene_symbol'=> ['required', $symbolRule],
            'expert_panel_id' => 'required',
            'moi_id' => 'nullable|exists:mode_of_inheritances,id',
            'gdm_uuid' => 'nullable|regex:/^\w{8}(-(\w){4}){3}-\w{12}$/'
        ];
    }

    public function messages()
    {
        return [
            'rationale_other.required_if' => 'Please provide details about your rational'
        ];
    }
}
