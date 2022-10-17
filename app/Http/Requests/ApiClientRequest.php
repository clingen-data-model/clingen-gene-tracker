<?php

namespace App\Http\Requests;

use App\ApiClient;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class ApiClientRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return request()->user()->hasAnyRole(['programmer', 'admin']);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $apiClient = new ApiClient();
        if ($this->id) {
            $apiClient = ApiClient::findOrFail($this->id);
        }
        
        return [
            'name' => ['required', Rule::unique('api_clients', 'name')->ignore($apiClient)],
            'contact_email' => ['required', 'email']
        ];
    }
}
