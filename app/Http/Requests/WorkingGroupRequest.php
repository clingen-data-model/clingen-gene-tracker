<?php

namespace App\Http\Requests;

use App\WorkingGroup;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WorkingGroupRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $permission = $this->isMethod('post')
            ? 'create working-groups'
            : 'update working-groups';

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
        $workingGroup = $this->route('working_group');
        $workingGroupId = $workingGroup instanceof WorkingGroup
            ? $workingGroup->getKey()
            : $workingGroup;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('working_groups', 'name')->ignore($workingGroupId),
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
