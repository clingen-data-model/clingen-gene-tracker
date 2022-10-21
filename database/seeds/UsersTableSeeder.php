<?php

namespace Database\Seeders;

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
        $epIds = ExpertPanel::all();
        $user = User::updateOrCreate(
            ['email' => 'super.user@example.com'],
            [
                'name' => 'Super User',
                'password' => 'tester',
            ]
        );
        $user->assignRole('programmer');

        $user = User::updateOrCreate(
            ['email' => 'some.admin@example.com'],
            [
                'name' => 'Some Admin',
                'password' => 'tester',
            ]
        );
        $user->assignRole('admin');
        $user->expertPanels()->sync([$epIds->first()->id, $epIds->get(2)->id]);

        if (!app()->environment('production')) {
            $user = User::updateOrCreate(
                ['email' => 'james-curatorn@example.com'],
                [
                    'name' => 'James A Curator',
                    'password' => 'tester',
                ]
            );
            $user->expertPanels()->sync(
                [
                    $epIds->get(1)->id => ['is_curator' => 1],
                    $epIds->get(3)->id => ['is_curator' => 1],
                ]
            );

            $user = User::updateOrCreate(
                ['email' => 'eugenia-kirator@example.com'],
                [
                    'name' => 'Eugenia Kirator',
                    'password' => 'tester',
                ]
            );
            $user->expertPanels()
                ->sync(
                    [
                        $epIds->get(0)->id => ['is_curator' => 1],
                        $epIds->get(3)->id => ['is_curator' => 1, 'can_edit_curations' => 1],
                    ]
                );

            $user = User::updateOrCreate(
                ['email' => 'sara-coordinator@example.com'],
                [
                    'name' => 'Sarah Coordinator',
                    'password' => 'tester',
                ]
            );
            $user->expertPanels()->sync(
                [
                    $epIds->first()->id => ['is_coordinator' => 1, 'can_edit_curations' => 1],
                ]
            );

            $viewer = User::updateOrCreate(
                ['email'=> 'viewer@example.com'],
                [
                    'name' => 'Curation Viewer',
                    'password' => 'tester'
                ]
            );

            $viewer->assignRole('viewer');
        }
    }
}
