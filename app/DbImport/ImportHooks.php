<?php

namespace App\DbImport;

use App\User;

class ImportHooks
{
    public static function beforeImport()
    {
        // code here
    }

    public static function afterImport()
    {
        if (!User::where('email', 'sirs@unc.edu')->get()->first()) {
            $user = factory(\App\User::class)->create([
                'name'=>'Sirs Programmer',
                'email'=>'sirs@unc.edu',
                'password'=>'tester'
            ]);
            $user->roles()->attach(1);
        }
    }
}
