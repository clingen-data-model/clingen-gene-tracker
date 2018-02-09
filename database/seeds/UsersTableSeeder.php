<?php

use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        factory(\App\User::class)->create([
            'name' => 'Sirs Programmer',
            'email' => 'sirs@unc.edu',
            'password' => \Hash::make('tester')
        ]);

        factory(\App\User::class, 3)->create();
    }
}
