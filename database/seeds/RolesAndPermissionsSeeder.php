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

        // create roles and assign existing permissions
        $role = Role::create(['name' => 'programmer']);
        $role->givePermissionTo('list users');
        $role->givePermissionTo('create users');
        $role->givePermissionTo('update users');
        $role->givePermissionTo('deactivate users');
        $role->givePermissionTo('delete users');

        $role = Role::create(['name' => 'admin']);
        $role->givePermissionTo('list users');
        $role->givePermissionTo('create users');
        $role->givePermissionTo('update users');
        $role->givePermissionTo('deactivate users');
        $role->givePermissionTo('delete users');

        Role::create(['name' => 'coordinator']);
        Role::create(['name' => 'curator']);

    }
}
