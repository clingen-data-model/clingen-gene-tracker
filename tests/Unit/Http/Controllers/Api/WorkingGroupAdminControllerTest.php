<?php

namespace Tests\Unit\Http\Controllers\Api;

use App\ExpertPanel;
use App\User;
use App\WorkingGroup;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

#[\PHPUnit\Framework\Attributes\Group('api')]
#[\PHPUnit\Framework\Attributes\Group('admin-working-groups')]
class WorkingGroupAdminControllerTest extends TestCase
{
    use DatabaseTransactions;

    private User $programmer;

    public function setUp(): void
    {
        parent::setUp();

        $permissions = collect(['list', 'create', 'update', 'delete'])->map(
            fn ($operation) => Permission::firstOrCreate([
                'name' => "{$operation} working-groups",
                'guard_name' => 'web',
            ])
        );
        $role = Role::firstOrCreate(['name' => 'programmer', 'guard_name' => 'web']);
        $role->givePermissionTo($permissions);
        $this->programmer = factory(User::class)->create();
        $this->programmer->assignRole($role);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function authorized_user_can_list_create_update_and_delete_unreferenced_working_groups(): void
    {
        $listed = factory(WorkingGroup::class)->create(['name' => 'Listed Working Group']);
        factory(ExpertPanel::class, 2)->create(['working_group_id' => $listed->id]);

        $this->actingAs($this->programmer, 'api')->getJson('/api/admin/working-groups')
            ->assertOk()->assertJsonFragment([
                'id' => $listed->id,
                'name' => 'Listed Working Group',
                'expert_panels_count' => 2,
            ]);

        $created = $this->actingAs($this->programmer, 'api')->postJson('/api/admin/working-groups', [
            'name' => 'Created Working Group',
            'affiliation_id' => 999999,
        ])->assertCreated()->assertJsonMissing(['affiliation_id' => 999999])->json();

        $this->actingAs($this->programmer, 'api')->putJson("/api/admin/working-groups/{$created['id']}", [
            'name' => 'Updated Working Group',
            'affiliation_id' => 999999,
        ])->assertOk()->assertJsonFragment([
            'name' => 'Updated Working Group',
            'expert_panels_count' => 0,
        ]);

        $this->actingAs($this->programmer, 'api')->deleteJson("/api/admin/working-groups/{$created['id']}")
            ->assertNoContent();
        $this->assertSoftDeleted('working_groups', ['id' => $created['id']]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function name_is_required_and_unique_while_an_unchanged_update_is_valid(): void
    {
        $workingGroup = factory(WorkingGroup::class)->create(['name' => 'Unique Working Group']);

        $this->actingAs($this->programmer, 'api')->postJson('/api/admin/working-groups', [])
            ->assertStatus(422)->assertJsonValidationErrors('name');
        $this->actingAs($this->programmer, 'api')->postJson('/api/admin/working-groups', [
            'name' => 'Unique Working Group',
        ])->assertStatus(422)->assertJsonValidationErrors('name');
        $this->actingAs($this->programmer, 'api')->putJson("/api/admin/working-groups/{$workingGroup->id}", [
            'name' => 'Unique Working Group',
        ])->assertOk();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function working_group_with_expert_panels_cannot_be_deleted(): void
    {
        $workingGroup = factory(WorkingGroup::class)->create();
        factory(ExpertPanel::class)->create(['working_group_id' => $workingGroup->id]);

        $this->actingAs($this->programmer, 'api')->deleteJson("/api/admin/working-groups/{$workingGroup->id}")
            ->assertStatus(409)
            ->assertJson(['message' => 'This working group has expert panels and cannot be deleted.']);
        $this->assertNull($workingGroup->fresh()->deleted_at);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_api_rejects_missing_role_and_operation_permissions(): void
    {
        $workingGroup = factory(WorkingGroup::class)->create();
        $viewer = factory(User::class)->create();
        $admin = factory(User::class)->create();
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $adminRole->revokePermissionTo('create working-groups');
        $adminRole->revokePermissionTo('delete working-groups');
        $admin->assignRole($adminRole);

        $this->actingAs($viewer, 'api')->getJson('/api/admin/working-groups')->assertForbidden();
        $this->actingAs($admin, 'api')->postJson('/api/admin/working-groups', ['name' => 'Forbidden Group'])
            ->assertForbidden();

        $this->actingAs($admin, 'api')->deleteJson("/api/admin/working-groups/{$workingGroup->id}")
            ->assertForbidden();
    }
}
