<?php

namespace Tests\Unit\Http\Controllers\Api;

use App\Affiliation;
use App\Curation;
use App\ExpertPanel;
use App\User;
use App\WorkingGroup;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

#[\PHPUnit\Framework\Attributes\Group('api')]
#[\PHPUnit\Framework\Attributes\Group('admin-expert-panels')]
class ExpertPanelAdminControllerTest extends TestCase
{
    use DatabaseTransactions;

    private User $programmer;

    public function setUp(): void
    {
        parent::setUp();

        $permissions = collect(['list', 'create', 'update', 'delete'])->map(
            fn ($operation) => Permission::firstOrCreate([
                'name' => "{$operation} expert-panels",
                'guard_name' => 'web',
            ])
        );
        $role = Role::firstOrCreate(['name' => 'programmer', 'guard_name' => 'web']);
        $role->givePermissionTo($permissions);
        $this->programmer = factory(User::class)->create();
        $this->programmer->assignRole($role);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function authorized_user_can_list_panels_with_management_relationships_and_counts(): void
    {
        $workingGroup = factory(WorkingGroup::class)->create(['name' => 'Panel Working Group']);
        $affiliation = factory(Affiliation::class)->create(['clingen_id' => '40099', 'name' => 'Panel Affiliation']);
        $panel = factory(ExpertPanel::class)->create([
            'name' => 'Listed Expert Panel',
            'working_group_id' => $workingGroup->id,
            'affiliation_id' => $affiliation->id,
        ]);
        $panel->users()->attach(factory(User::class)->create()->id);
        factory(Curation::class)->create(['expert_panel_id' => $panel->id]);

        $response = $this->actingAs($this->programmer, 'api')->getJson('/api/admin/expert-panels?per_page=100')
            ->assertOk();
        $response->assertJsonStructure(['data', 'current_page', 'per_page', 'total']);
        $listedPanel = collect($response->json('data'))->firstWhere('id', $panel->id);

        $this->assertSame('Listed Expert Panel', $listedPanel['name']);
        $this->assertSame(1, $listedPanel['curations_count']);
        $this->assertSame(1, $listedPanel['users_count']);
        $this->assertSame('Panel Working Group', $listedPanel['working_group']['name']);
        $this->assertSame(40099, $listedPanel['affiliation']['clingen_id']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function expert_panel_listing_paginates_and_bounds_the_page_size(): void
    {
        factory(ExpertPanel::class, 3)->create();

        $this->actingAs($this->programmer, 'api')->getJson('/api/admin/expert-panels?per_page=2')
            ->assertOk()->assertJsonCount(2, 'data')->assertJsonPath('per_page', 2);
        $this->actingAs($this->programmer, 'api')->getJson('/api/admin/expert-panels?per_page=101')
            ->assertOk()->assertJsonPath('per_page', 100);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function authorized_user_can_create_and_update_only_local_fields(): void
    {
        $firstGroup = factory(WorkingGroup::class)->create();
        $secondGroup = factory(WorkingGroup::class)->create();
        $affiliation = factory(Affiliation::class)->create(['clingen_id' => 40100]);
        $otherAffiliation = factory(Affiliation::class)->create(['clingen_id' => 40101]);

        $created = $this->actingAs($this->programmer, 'api')->postJson('/api/admin/expert-panels', [
            'name' => 'Created Expert Panel',
            'working_group_id' => $firstGroup->id,
            'affiliation_id' => $affiliation->id,
        ])->assertCreated()->assertJsonPath('affiliation_id', null)->json();

        $member = factory(User::class)->create();
        $createdPanel = ExpertPanel::findOrFail($created['id']);
        $createdPanel->update(['affiliation_id' => $affiliation->id]);
        $createdPanel->users()->attach($member->id, ['is_curator' => true]);

        $this->actingAs($this->programmer, 'api')->putJson("/api/admin/expert-panels/{$created['id']}", [
            'name' => 'Updated Expert Panel',
            'working_group_id' => $secondGroup->id,
            'affiliation_id' => $otherAffiliation->id,
        ])->assertOk()
            ->assertJsonPath('name', 'Updated Expert Panel')
            ->assertJsonPath('working_group_id', $secondGroup->id)
            ->assertJsonPath('affiliation_id', $affiliation->id)
            ->assertJsonPath('users_count', 1);

        $this->assertDatabaseHas('expert_panel_user', [
            'expert_panel_id' => $created['id'],
            'user_id' => $member->id,
            'is_curator' => 1,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function validation_requires_a_unique_name_and_an_active_working_group(): void
    {
        $panel = factory(ExpertPanel::class)->create(['name' => 'Unique Expert Panel']);
        $deletedGroup = factory(WorkingGroup::class)->create();
        $deletedGroup->delete();

        $this->actingAs($this->programmer, 'api')->postJson('/api/admin/expert-panels', [])
            ->assertStatus(422)->assertJsonValidationErrors('name');
        $this->actingAs($this->programmer, 'api')->postJson('/api/admin/expert-panels', [
            'name' => 'Unique Expert Panel',
            'working_group_id' => $deletedGroup->id,
        ])->assertStatus(422)->assertJsonValidationErrors(['name', 'working_group_id']);
        $this->actingAs($this->programmer, 'api')->putJson("/api/admin/expert-panels/{$panel->id}", [
            'name' => 'Unique Expert Panel',
            'working_group_id' => null,
        ])->assertOk();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_api_enforces_role_and_operation_permissions(): void
    {
        $viewer = factory(User::class)->create();
        $admin = factory(User::class)->create();
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $adminRole->revokePermissionTo('create expert-panels');
        $adminRole->revokePermissionTo('update expert-panels');
        $admin->assignRole($adminRole);
        $panel = factory(ExpertPanel::class)->create();

        $this->actingAs($viewer, 'api')->getJson('/api/admin/expert-panels')->assertForbidden();
        $this->actingAs($admin, 'api')->postJson('/api/admin/expert-panels', ['name' => 'Forbidden Panel'])
            ->assertForbidden();
        $this->actingAs($admin, 'api')->putJson("/api/admin/expert-panels/{$panel->id}", [
            'name' => 'Forbidden Update',
        ])->assertForbidden();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function deletion_is_not_an_admin_operation(): void
    {
        $panel = factory(ExpertPanel::class)->create();

        $this->actingAs($this->programmer, 'api')->deleteJson("/api/admin/expert-panels/{$panel->id}")
            ->assertStatus(405);
        $this->assertDatabaseHas('expert_panels', ['id' => $panel->id]);
    }
}
