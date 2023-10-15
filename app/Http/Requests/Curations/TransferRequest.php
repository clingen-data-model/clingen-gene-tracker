<?php

namespace App\Http\Requests\Curations;

use Illuminate\Foundation\Http\FormRequest;

class TransferRequest extends FormRequest
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
        return [
            'expert_panel_id' => 'required|exists:expert_panels,id',
            'start_date' => 'required|date',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'This field is required.',
        ];
    }
}
