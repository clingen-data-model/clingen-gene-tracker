<?php

namespace App\Http\Requests\Admin;

use App\Affiliation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AffiliationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $affiliation = new Affiliation();
        if ($this->id) {
            $affiliation = Affiliation::findOrFail($this->id);
        }

        return [
            'name' => ['required', Rule::unique('affiliations', 'name')->ignore($affiliation)],
            'short_name' => ['required', 'max:15', Rule::unique('affiliations', 'short_name')->ignore($affiliation)],
            'affiliation_type_id' => 'required|exists:affiliation_types,id',
            'parent_id' => 'nullable|exists:affiliations,id',
            'clingen_id' => ['required', 'size:5', Rule::unique('affiliations', 'clingen_id')->ignore($affiliation)],
        ];
    }
}
