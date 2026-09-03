<?php

namespace App\Http\Requests;

use App\Rationale;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RationaleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $permission = $this->isMethod('post')
            ? 'create rationales'
            : 'update rationales';

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
        $rationale = $this->route('rationale');
        $rationaleId = $rationale instanceof Rationale ? $rationale->getKey() : $rationale;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('rationales', 'name')->ignore($rationaleId),
            ],
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
            //
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
            //
        ];
    }
}
