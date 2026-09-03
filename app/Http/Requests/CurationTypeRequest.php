<?php

namespace App\Http\Requests;

use App\CurationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CurationTypeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $permission = $this->isMethod('post')
            ? 'create curation-types'
            : 'update curation-types';

        return $this->user()
            && $this->user()->hasAnyRole(['admin', 'programmer'])
            && $this->user()->hasPermissionTo($permission);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $curationType = $this->route('curation_type');
        $curationTypeId = $curationType instanceof CurationType
            ? $curationType->getKey()
            : $curationType;

        return [
            'name' => [
                'required',
                'min:5',
                'max:255',
                Rule::unique('curation_types', 'name')->ignore($curationTypeId),
            ],
            'description' => ['nullable', 'string'],
        ];
    }

    /**
     * Get the validation attributes that apply to the request.
     *
     * @return array
     */
    public function attributes()
    {
        return [

        ];
    }

    /**
     * Get the validation messages that apply to the request.
     *
     * @return array
     */
    public function messages()
    {
        return [

        ];
    }
}
