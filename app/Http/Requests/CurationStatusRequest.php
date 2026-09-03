<?php

namespace App\Http\Requests;

use App\CurationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CurationStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $permission = $this->isMethod('post')
            ? 'create curation-statuses'
            : 'update curation-statuses';

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
        $status = $this->route('curation_status');
        $statusId = $status instanceof CurationStatus ? $status->getKey() : $status;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('curation_statuses', 'name')->ignore($statusId),
            ],
            'description' => ['nullable', 'string'],
        ];
    }
}
