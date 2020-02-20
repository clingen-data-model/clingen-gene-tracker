<?php

use App\User;
use App\ExpertPanel;
use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $epIds = ExpertPanel::all()->pluck('id', 'id')->toArray();
        $user = User::updateOrCreate(
            ['email' => 'sirs@unc.edu'],
            [
                'name' => 'Sirs Programmer',
                'password' => 'tester',
            ]
        );
        $user->assignRole('programmer');

        $user = User::updateOrCreate(
            ['email' => 'jward3@email.unc.edu'],
            [
                'name' => 'TJ Ward',
                'password' => 'tester',
            ]
        );
        $user->assignRole('programmer');

        $user = User::updateOrCreate(
            ['email' => 'maria.tobin@unc.edu'],
            [
                'name' => 'Maria Tobin',
                'password' => 'tester',
            ]
        );
        $user->assignRole('programmer');

        $user = User::updateOrCreate(
            ['email' => 'goldjen@email.unc.edu'],
            [
                'name' => 'Jenny Goldstein',
                'password' => 'tester',
            ]
        );
        $user->assignRole('admin');
        $user->expertPanels()->sync([$epIds[1]]);

        $user = User::updateOrCreate(
            ['email' => 'courtney_thaxton@med.unc.edu'],
            [
                'name' => 'Courtney Lynn Thaxton',
                'password' => 'tester',
            ]
        );
        $user->assignRole('admin');
        $user->expertPanels()->sync([$epIds[1], $epIds[3]]);

        if (!env('production')) {
            $user = User::updateOrCreate(
                ['email' => 'james-curatorn@med.unc.edu'],
                [
                    'name' => 'James A Curator',
                    'password' => 'tester',
                ]
            );
            $user->expertPanels()->sync(
                [
                    $epIds[2] => ['is_curator' => 1],
                    $epIds[4] => ['is_curator' => 1],
                ]
            );

            $user = User::updateOrCreate(
                ['email' => 'eugenia-kirator@med.unc.edu'],
                [
                    'name' => 'Eugenia Kirator',
                    'password' => 'tester',
                ]
            );
            $user->expertPanels()
                ->sync(
                    [
                        $epIds[1] => ['is_curator' => 1],
                        $epIds[4] => ['is_curator' => 1, 'can_edit_curations' => 1],
                    ]
                );

            $user = User::updateOrCreate(
                ['email' => 'sara-coordinator@med.unc.edu'],
                [
                    'name' => 'Sarah Coordinator',
                    'password' => 'tester',
                ]
            );
            $user->expertPanels()->sync(
                [
                    $epIds[1] => ['is_coordinator' => 1, 'can_edit_curations' => 1],
                ]
            );
        }
    }
}
