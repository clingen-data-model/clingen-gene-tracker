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
        $user = factory(\App\User::class)->create([
            'name' => 'Sirs Programmer',
            'email' => 'sirs@unc.edu',
            'password' => \Hash::make('tester')
        ]);
        $user->assignRole('programmer');

        $curator = factory(\App\User::class, 3)->create();
        $curator->each(function ($u) {
            $u->assignRole('curator');
        });

        factory(\App\User::class, 3)->create();
    }
}
