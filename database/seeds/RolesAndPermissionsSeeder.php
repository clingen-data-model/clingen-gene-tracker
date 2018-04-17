<?php

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Reset cached roles and permissions
        app()['cache']->forget('spatie.permission.cache');

        // create permissions
        Permission::create(['name' => 'list users']);
        Permission::create(['name' => 'create users']);
        Permission::create(['name' => 'update users']);
        Permission::create(['name' => 'deactivate users']);
        Permission::create(['name' => 'delete users']);

        Permission::create(['name' => 'list expert-panels']);
        Permission::create(['name' => 'create expert-panels']);
        Permission::create(['name' => 'update expert-panels']);
        Permission::create(['name' => 'deactivate expert-panels']);

        Permission::create(['name' => 'list topic-statuses']);
        Permission::create(['name' => 'create topic-statuses']);
        Permission::create(['name' => 'update topic-statuses']);
        Permission::create(['name' => 'delete topic-statuses']);

        /**
         * Programmer role can do everything
         */
        $role = Role::create(['name' => 'programmer']);
        $role->givePermissionTo('list users');
        $role->givePermissionTo('create users');
        $role->givePermissionTo('update users');
        $role->givePermissionTo('deactivate users');
        $role->givePermissionTo('delete users');

        $role->givePermissionTo('list expert-panels');
        $role->givePermissionTo('create expert-panels');
        $role->givePermissionTo('update expert-panels');
        $role->givePermissionTo('deactivate expert-panels');

        $role->givePermissionTo('list topic-statuses');
        $role->givePermissionTo('create topic-statuses');
        $role->givePermissionTo('update topic-statuses');
        $role->givePermissionTo('delete topic-statuses');

        /**
         * Admin Role can do most things
         */
        $role = Role::create(['name' => 'admin']);
        $role->givePermissionTo('list users');
        $role->givePermissionTo('create users');
        $role->givePermissionTo('update users');
        $role->givePermissionTo('deactivate users');
        $role->givePermissionTo('delete users');

        $role->givePermissionTo('list expert-panels');
        $role->givePermissionTo('create expert-panels');
        $role->givePermissionTo('update expert-panels');
        $role->givePermissionTo('deactivate expert-panels');

        $role->givePermissionTo('list topic-statuses');
        $role->givePermissionTo('create topic-statuses');
        $role->givePermissionTo('update topic-statuses');

        Role::create(['name' => 'coordinator']);
        Role::create(['name' => 'curator']);
    }
}
