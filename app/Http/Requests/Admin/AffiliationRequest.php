<?php

namespace App\Http\Requests\Admin;

use App\Affiliation;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class AffiliationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user() && $this->user()->hasAnyRole(['admin', 'programmer']);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $affiliation = $this->route('affiliation');

        return [
            'short_name' => [
                'nullable',
                'string',
                'max:15',
                Rule::unique('affiliations', 'short_name')
                    ->where(fn ($query) => $query->where('affiliation_type_id', $affiliation->affiliation_type_id))
                    ->ignore($affiliation->id),
            ],
        ];
    }
}
