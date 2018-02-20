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
        $this->call(UsersTableSeeder::class);
        $this->call(ExpertPanelsTableSeeder::class);
        $this->call(TopicsTableSeeder::class);
        $this->call( RolesAndPermissionsSeeder::class);
    }
}
