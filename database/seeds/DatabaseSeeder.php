<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $this->call(MOIsTableSeeder::class);
        $this->call(AffiliationTypesTableSeeder::class);
        $this->call(AffiliationsTableSeeder::class);
        $this->call(RolesAndPermissionsSeeder::class);
        if (\DB::getDatabaseName() == 'testing') {
            $this->call(WorkingGroupsTableSeeder::class);
            $this->call(ExpertPanelsTableSeeder::class);
            $this->call(UsersTableSeeder::class);
            $this->call(TestGeneSeeder::class);
        }
        $this->call(CurationStatusesTableSeeder::class);
        $this->call(CurationTypesTableSeeder::class);
        $this->call(RationalesTableSeeder::class);
        $this->call(CurationsTableSeeder::class);
        $this->call(AppStatesTableSeeder::class);
    }
}
