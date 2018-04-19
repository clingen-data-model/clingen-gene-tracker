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
        Permission::firstOrcreate(['name' => 'list users']);
        Permission::firstOrcreate(['name' => 'create users']);
        Permission::firstOrcreate(['name' => 'update users']);
        Permission::firstOrcreate(['name' => 'deactivate users']);
        Permission::firstOrcreate(['name' => 'delete users']);

        Permission::firstOrcreate(['name' => 'list expert-panels']);
        Permission::firstOrcreate(['name' => 'create expert-panels']);
        Permission::firstOrcreate(['name' => 'update expert-panels']);
        Permission::firstOrcreate(['name' => 'deactivate expert-panels']);

        Permission::firstOrcreate(['name' => 'list topic-statuses']);
        Permission::firstOrcreate(['name' => 'create topic-statuses']);
        Permission::firstOrcreate(['name' => 'update topic-statuses']);
        Permission::firstOrcreate(['name' => 'delete topic-statuses']);

        Permission::firstOrcreate(['name' => 'list working-groups']);
        Permission::firstOrcreate(['name' => 'create working-groups']);
        Permission::firstOrcreate(['name' => 'update working-groups']);
        Permission::firstOrcreate(['name' => 'delete working-groups']);

        /**
         * Programmer role can do everything
         */
        $role = Role::firstOrcreate(['name' => 'programmer']);
        if (!$role->hasPermissionTo('list users')) {
            $role->givePermissionTo('list users');
        }
        if (!$role->hasPermissionTo('create users')) {
            $role->givePermissionTo('create users');
        }
        if (!$role->hasPermissionTo('update users')) {
            $role->givePermissionTo('update users');
        }
        if (!$role->hasPermissionTo('deactivate users')) {
            $role->givePermissionTo('deactivate users');
        }
        if (!$role->hasPermissionTo('delete users')) {
            $role->givePermissionTo('delete users');
        }

        if (!$role->hasPermissionTo('list expert-panels')) {
            $role->givePermissionTo('list expert-panels');
        }
        if (!$role->hasPermissionTo('create expert-panels')) {
            $role->givePermissionTo('create expert-panels');
        }
        if (!$role->hasPermissionTo('update expert-panels')) {
            $role->givePermissionTo('update expert-panels');
        }
        if (!$role->hasPermissionTo('deactivate expert-panels')) {
            $role->givePermissionTo('deactivate expert-panels');
        }

        if (!$role->hasPermissionTo('list topic-statuses')) {
            $role->givePermissionTo('list topic-statuses');
        }
        if (!$role->hasPermissionTo('create topic-statuses')) {
            $role->givePermissionTo('create topic-statuses');
        }
        if (!$role->hasPermissionTo('update topic-statuses')) {
            $role->givePermissionTo('update topic-statuses');
        }
        if (!$role->hasPermissionTo('delete topic-statuses')) {
            $role->givePermissionTo('delete topic-statuses');
        }

        if (!$role->hasPermissionTo('list working-groups')) {
            $role->givePermissionTo('list working-groups');
        }
        if (!$role->hasPermissionTo('create working-groups')) {
            $role->givePermissionTo('create working-groups');
        }
        if (!$role->hasPermissionTo('update working-groups')) {
            $role->givePermissionTo('update working-groups');
        }
        if (!$role->hasPermissionTo('delete working-groups')) {
            $role->givePermissionTo('delete working-groups');
        }

        /**
         * Admin Role can do most things
         */
        $role = Role::firstOrcreate(['name' => 'admin']);
        if (!$role->hasPermissionTo('list users')) {
            $role->givePermissionTo('list users');
        }
        if (!$role->hasPermissionTo('create users')) {
            $role->givePermissionTo('create users');
        }
        if (!$role->hasPermissionTo('update users')) {
            $role->givePermissionTo('update users');
        }
        if (!$role->hasPermissionTo('deactivate users')) {
            $role->givePermissionTo('deactivate users');
        }
        if (!$role->hasPermissionTo('delete users')) {
            $role->givePermissionTo('delete users');
        }

        if (!$role->hasPermissionTo('list expert-panels')) {
            $role->givePermissionTo('list expert-panels');
        }
        if (!$role->hasPermissionTo('create expert-panels')) {
            $role->givePermissionTo('create expert-panels');
        }
        if (!$role->hasPermissionTo('update expert-panels')) {
            $role->givePermissionTo('update expert-panels');
        }
        if (!$role->hasPermissionTo('deactivate expert-panels')) {
            $role->givePermissionTo('deactivate expert-panels');
        }

        if (!$role->hasPermissionTo('list topic-statuses')) {
            $role->givePermissionTo('list topic-statuses');
        }
        if (!$role->hasPermissionTo('create topic-statuses')) {
            $role->givePermissionTo('create topic-statuses');
        }
        if (!$role->hasPermissionTo('update topic-statuses')) {
            $role->givePermissionTo('update topic-statuses');
        }

        if (!$role->hasPermissionTo('list working-groups')) {
            $role->givePermissionTo('list working-groups');
        }
        if (!$role->hasPermissionTo('create working-groups')) {
            $role->givePermissionTo('create working-groups');
        }
        if (!$role->hasPermissionTo('update working-groups')) {
            $role->givePermissionTo('update working-groups');
        }
        if (!$role->hasPermissionTo('delete working-groups')) {
            $role->givePermissionTo('delete working-groups');
        }

        Role::firstOrcreate(['name' => 'coordinator']);
        Role::firstOrcreate(['name' => 'curator']);
    }
}
