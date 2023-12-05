<?php

namespace App\Http\Requests;

use App\ApiClient;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApiClientRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return request()->user()->hasAnyRole(['programmer', 'admin']);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $apiClient = new ApiClient();
        if ($this->id) {
            $apiClient = ApiClient::findOrFail($this->id);
        }

        return [
            'name' => ['required', Rule::unique('api_clients', 'name')->ignore($apiClient)],
            'contact_email' => ['required', 'email'],
        ];
    }
}
