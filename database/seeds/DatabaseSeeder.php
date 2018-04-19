<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->call(RolesAndPermissionsSeeder::class);
        $this->call(UsersTableSeeder::class);
        $this->call(TopicStatusesTableSeeder::class);
        $this->call(WorkingGroupsTableSeeder::class);
        $this->call(ExpertPanelsTableSeeder::class);
        $this->call(TopicsTableSeeder::class);
    }
}
