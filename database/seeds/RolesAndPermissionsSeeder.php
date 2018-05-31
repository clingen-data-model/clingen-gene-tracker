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
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        // create permissions
        $this->createPermissionGroup('users', ['list','create','update','delete','deactivate']);
        $this->createPermissionGroup('expert-panels', ['list','create','update','delete','deactivate']);
        $this->createPermissionGroup('topic-statuses');
        $this->createPermissionGroup('working-groups');
        $this->createPermissionGroup('curation-types');
        $this->createPermissionGroup('rationales');
        $this->createPermissionGroup('pages');
        $this->createPermissionGroup('topics');

        /**
         * Programmer role can do everything
         */
        $role = Role::firstOrcreate(['name' => 'programmer']);
        $this->givePermissionsToRole($role, 'users', ['list', 'create','update', 'deactivate', 'delete']);
        $this->givePermissionsToRole($role, 'expert-panels', ['list', 'create','update', 'deactivate', 'delete']);
        $this->givePermissionsToRole($role, 'topic-statuses');
        $this->givePermissionsToRole($role, 'working-groups');
        $this->givePermissionsToRole($role, 'curation-types');
        $this->givePermissionsToRole($role, 'rationales');
        $this->givePermissionsToRole($role, 'pages');
        $this->givePermissionsToRole($role, 'topics');

        /**
         * Admin Role can do most things
         */
        $role = Role::firstOrcreate(['name' => 'admin']);
        $this->givePermissionsToRole($role, 'users', ['list', 'create','update', 'deactivate', 'delete']);
        $this->givePermissionsToRole($role, 'expert-panels', ['list', 'create','update', 'deactivate', 'delete']);
        $this->givePermissionsToRole($role, 'topic-statuses');
        $this->givePermissionsToRole($role, 'working-groups');
        $this->givePermissionsToRole($role, 'rationales', ['list','update']);
        $this->givePermissionsToRole($role, 'pages', ['list', 'update']);
        $this->givePermissionsToRole($role, 'topics');

        $role = Role::firstOrcreate(['name' => 'coordinator']);

        Role::firstOrcreate(['name' => 'curator']);
    }

    protected function givePermissionsToRole($role, $entity, $actions = null)
    {
        $actions = $actions ?? ['list', 'create', 'update', 'delete'];
        foreach ($actions as $action) {
            $perm = $action.' '.$entity;
            if (!$role->hasPermissionTo($perm)) {
                $role->givePermissionTo($perm);
            }
        }
    }

    protected function createPermissionGroup($entity, $actions = null)
    {
        $actions = $actions ?? ['list', 'create', 'update', 'delete'];
        foreach ($actions as $action) {
            $perm = $action.' '.$entity;
            Permission::firstOrcreate(['name' => $perm]);
        }
    }
}
