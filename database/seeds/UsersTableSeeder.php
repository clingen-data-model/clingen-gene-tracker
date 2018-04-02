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

        $user = factory(\App\User::class)->create([
            'name' => 'TJ Ward',
            'email' => 'jward3@email.unc.edu',
            'password' => \Hash::make('tester')
        ]);
        $user->assignRole('programmer');

        $user = factory(\App\User::class)->create([
            'name' => 'Maria Tobin',
            'email' => 'maria.tobin@unc.edu',
            'password' => \Hash::make('tester')
        ]);
        $user->assignRole('programmer');

        $user = factory(\App\User::class)->create([
            'name' => 'Jenny Goldstein',
            'email' => 'goldjen@email.unc.edu',
            'password' => \Hash::make('tester')
        ]);
        $user->assignRole('admin');

        $user = factory(\App\User::class)->create([
            'name' => 'Courtney Lynn Thaxton',
            'email' => 'courtney_thaxton@med.unc.edu',
            'password' => \Hash::make('tester')
        ]);
        $user->assignRole('admin');
    }
}
