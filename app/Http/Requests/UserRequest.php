<?php

namespace App\Http\Requests;

use App\User;
use App\Http\Requests\Request;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // only allow updates if the user is logged in
        return \Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $user = new User();
        if ($this->route('id')) {
            $user = User::findOrFail($this->route('id'));
        }

        return [
            'name' => 'required|min:5|max:255',
            'email' => [
                        'required',
                        'email',
                        'max:255',
                        Rule::unique('users', 'email')->ignore($user)
                    ]
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
