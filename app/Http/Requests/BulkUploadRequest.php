<?php

namespace App\Http\Requests;

use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Http\FormRequest;

class BulkUploadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::user()->isCoordinator() || Auth::user()->hasAnyRole('programmer|admin');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'expert_panel_id' => 'required|exists:expert_panels,id',
            'bulk_curations' => 'required_without:path',
        ];
    }
}
