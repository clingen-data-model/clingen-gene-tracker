<?php

use App\User;
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
        $user = User::updateOrCreate([
            'name' => 'Sirs Programmer',
            'email' => 'sirs@unc.edu',
            'password' => 'tester'
        ]);
        $user->assignRole('programmer');

        $user = User::updateOrCreate([
            'name' => 'TJ Ward',
            'email' => 'jward3@email.unc.edu',
            'password' => 'tester'
        ]);
        $user->assignRole('programmer');

        $user = User::updateOrCreate([
            'name' => 'Maria Tobin',
            'email' => 'maria.tobin@unc.edu',
            'password' => 'tester'
        ]);
        $user->assignRole('programmer');

        $user = User::updateOrCreate([
            'name' => 'Jenny Goldstein',
            'email' => 'goldjen@email.unc.edu',
            'password' => 'tester'
        ]);
        $user->assignRole('admin');
        $user->expertPanels()->attach([1]);

        $user = User::updateOrCreate([
            'name' => 'Courtney Lynn Thaxton',
            'email' => 'courtney_thaxton@med.unc.edu',
            'password' => 'tester'
        ]);
        $user->assignRole('admin');
        $user->expertPanels()->attach([1, 3]);

        if (!env('production')) {
            $user = User::updateOrCreate([
                'name' => 'James A Curator',
                'email' => 'james-curatorn@med.unc.edu',
                'password' => 'tester'
            ]);
            $user->expertPanels()->attach([2=>['is_curator'=>1], 4=>['is_curator'=>1]]);

            $user = User::updateOrCreate([
                'name' => 'Eugenia Kirator',
                'email' => 'eugenia-kirator@med.unc.edu',
                'password' => 'tester'
            ]);
            $user->expertPanels()->attach([1=>['is_curator'=>1]]);
            $user->expertPanels()->attach([4=>['is_curator'=>1, 'can_edit_curations'=>1]]);

            $user = User::updateOrCreate([
                'name' => 'Sarah Coordinator',
                'email' => 'sara-coordinator@med.unc.edu',
                'password' => 'tester'
            ]);
            $user->expertPanels()->attach([1 => ['is_coordinator'=>1, 'can_edit_curations'=>1]]);
        }
    }
}
