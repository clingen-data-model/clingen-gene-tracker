<?php

namespace Tests\Unit\Http\Controllers\Api;

use App\Affiliation;
use App\ExpertPanel;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

#[\PHPUnit\Framework\Attributes\Group('api')]
#[\PHPUnit\Framework\Attributes\Group('admin-users')]
class UserAdminControllerTest extends TestCase
{
    use DatabaseTransactions;

    private User $programmer;
    private Role $viewerRole;
    private Permission $directPermission;

    public function setUp(): void
    {
        parent::setUp();
        Notification::fake();

        $permissions = collect(['list users', 'update users', 'deactivate users'])->map(
            fn ($name) => Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web'])
        );
        $programmerRole = Role::firstOrCreate(['name' => 'programmer', 'guard_name' => 'web']);
        $programmerRole->givePermissionTo($permissions);
        $this->viewerRole = Role::firstOrCreate(['name' => 'viewer', 'guard_name' => 'web']);
        $this->directPermission = Permission::firstOrCreate([
            'name' => 'list curations',
            'guard_name' => 'web',
        ]);
        $this->programmer = factory(User::class)->create();
        $this->programmer->assignRole($programmerRole);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function authorized_user_can_list_users_and_controlled_role_permission_options(): void
    {
        $target = factory(User::class)->create(['name' => 'Managed User']);
        $target->assignRole($this->viewerRole);
        $target->givePermissionTo($this->directPermission);
        $target->expertPanels()->attach(factory(ExpertPanel::class)->create()->id);
        $target->affiliations()->attach(factory(Affiliation::class)->create(['clingen_id' => '49001'])->id);

        $response = $this->actingAs($this->programmer, 'api')->getJson('/api/admin/users')->assertOk();
        $listed = collect($response->json())->firstWhere('id', $target->id);
        $this->assertSame('viewer', $listed['roles'][0]['name']);
        $this->assertSame('list curations', $listed['permissions'][0]['name']);
        $this->assertSame(1, $listed['expert_panels_count']);
        $this->assertSame(1, $listed['affiliations_count']);

        $this->actingAs($this->programmer, 'api')->getJson('/api/admin/users/options')
            ->assertOk()
            ->assertJsonFragment(['id' => $this->viewerRole->id, 'name' => 'viewer'])
            ->assertJsonFragment(['id' => $this->directPermission->id, 'name' => 'list curations']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function authorized_user_can_update_identity_roles_and_direct_permissions_without_changing_memberships(): void
    {
        $target = factory(User::class)->create(['name' => 'Original Managed User']);
        $panel = factory(ExpertPanel::class)->create();
        $affiliation = factory(Affiliation::class)->create(['clingen_id' => '49002']);
        $target->expertPanels()->attach($panel->id, ['is_curator' => true, 'can_edit_curations' => true]);
        $target->affiliations()->attach($affiliation->id, ['is_coordinator' => true]);

        $this->actingAs($this->programmer, 'api')->putJson("/api/admin/users/{$target->id}", [
            'name' => 'Updated Managed User',
            'email' => 'updated-managed@example.com',
            'role_ids' => [$this->viewerRole->id],
            'permission_ids' => [$this->directPermission->id],
        ])->assertOk()
            ->assertJsonPath('name', 'Updated Managed User')
            ->assertJsonPath('email', 'updated-managed@example.com')
            ->assertJsonPath('expert_panels_count', 1)
            ->assertJsonPath('affiliations_count', 1);

        $target->refresh();
        $this->assertTrue($target->hasRole('viewer'));
        $this->assertTrue($target->permissions->contains('id', $this->directPermission->id));
        $this->assertDatabaseHas('expert_panel_user', [
            'user_id' => $target->id,
            'expert_panel_id' => $panel->id,
            'is_curator' => 1,
            'can_edit_curations' => 1,
        ]);
        $this->assertDatabaseHas('affiliation_user', [
            'user_id' => $target->id,
            'affiliation_id' => $affiliation->id,
            'is_coordinator' => 1,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function update_validates_identity_and_controlled_authorization_ids(): void
    {
        $first = factory(User::class)->create(['email' => 'unique-user@example.com']);
        $second = factory(User::class)->create();

        $this->actingAs($this->programmer, 'api')->putJson("/api/admin/users/{$second->id}", [
            'name' => 'Bad',
            'email' => $first->email,
            'role_ids' => [999999],
            'permission_ids' => [999999],
        ])->assertStatus(422)->assertJsonValidationErrors([
            'name', 'email', 'role_ids.0', 'permission_ids.0',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function authorized_user_can_deactivate_and_reactivate_an_account(): void
    {
        $target = factory(User::class)->create();

        $this->actingAs($this->programmer, 'api')
            ->patchJson("/api/admin/users/{$target->id}/deactivate")
            ->assertOk();
        $this->assertNotNull($target->fresh()->deactivated_at);

        $this->actingAs($this->programmer, 'api')
            ->patchJson("/api/admin/users/{$target->id}/reactivate")
            ->assertOk()->assertJsonPath('deactivated_at', null);
        $this->assertNull($target->fresh()->deactivated_at);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_api_enforces_shell_role_and_operation_permissions(): void
    {
        $viewer = factory(User::class)->create();
        $admin = factory(User::class)->create();
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $adminRole->syncPermissions([]);
        $admin->assignRole($adminRole);
        $target = factory(User::class)->create();
        $payload = [
            'name' => 'Forbidden Update', 'email' => 'forbidden@example.com',
            'role_ids' => [], 'permission_ids' => [],
        ];

        $this->actingAs($viewer, 'api')->getJson('/api/admin/users')->assertForbidden();
        $this->actingAs($admin, 'api')->putJson("/api/admin/users/{$target->id}", $payload)->assertForbidden();
        $this->actingAs($admin, 'api')->patchJson("/api/admin/users/{$target->id}/deactivate")->assertForbidden();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function creation_and_deletion_are_not_admin_operations(): void
    {
        $target = factory(User::class)->create();

        $this->actingAs($this->programmer, 'api')->postJson('/api/admin/users', [])->assertStatus(405);
        $this->actingAs($this->programmer, 'api')->deleteJson("/api/admin/users/{$target->id}")->assertStatus(405);
        $this->assertDatabaseHas('users', ['id' => $target->id, 'deleted_at' => null]);
    }
}
