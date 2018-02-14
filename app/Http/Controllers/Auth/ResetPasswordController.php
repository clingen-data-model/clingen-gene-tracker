<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\ResetsPasswords;

class ResetPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset requests
    | and uses a simple trait to include this behavior. You're free to
    | explore this trait and override any methods you wish to tweak.
    |
    */

    use ResetsPasswords;

    /**
     * Where to redirect users after resetting their password.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }
    
    protected function rules()
    {
        return [
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|confirmed|min:8|regex:/^(?=\S*[a-z])(?=\S*[A-Z])(?=\S*[\d])(?=\S*[‘~!@#\$%\^&\*\(\)\-_=\+\{\[\]\}\\\`\\/])\S*$/',
        ];
    }

    protected function validationErrorMessages()
    {
        return [
            'password.regex' => "Your password must contain at least 1 lower case character, at least 1 upper case character, at least one number, and at least 1 of the following ~!@#$%^&*()-_=+{[]}\`/"
        ];
    }
}
