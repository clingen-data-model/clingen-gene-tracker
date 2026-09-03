<?php

namespace App\Http\Requests;

use App\ExpertPanel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExpertPanelRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $permission = $this->isMethod('post')
            ? 'create expert-panels'
            : 'update expert-panels';

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
        $expertPanel = $this->route('expert_panel');
        $expertPanelId = $expertPanel instanceof ExpertPanel
            ? $expertPanel->getKey()
            : $expertPanel;

        return [
            'name' => [
                'required',
                'string',
                'min:5',
                'max:255',
                Rule::unique('expert_panels', 'name')->ignore($expertPanelId),
            ],
            'working_group_id' => [
                'nullable',
                Rule::exists('working_groups', 'id')->whereNull('deleted_at'),
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
