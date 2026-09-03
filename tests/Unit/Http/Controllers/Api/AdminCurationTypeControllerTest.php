<?php

namespace Tests\Unit\Http\Controllers\Api;

use App\Curation;
use App\CurationType;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

#[\PHPUnit\Framework\Attributes\Group('api')]
#[\PHPUnit\Framework\Attributes\Group('curation-types')]
class AdminCurationTypeControllerTest extends TestCase
{
    use DatabaseTransactions;

    private User $programmer;

    public function setUp(): void
    {
        parent::setUp();

        $permissions = collect(['list', 'create', 'update', 'delete'])
            ->map(fn ($operation) => Permission::firstOrCreate([
                'name' => $operation.' curation-types',
                'guard_name' => 'web',
            ]));
        $role = Role::firstOrCreate(['name' => 'programmer', 'guard_name' => 'web']);
        $role->syncPermissions($permissions);

        $this->programmer = factory(User::class)->create();
        $this->programmer->assignRole($role);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function authorized_user_can_list_curation_types(): void
    {
        $type = factory(CurationType::class)->create(['name' => 'Admin Listed Type']);

        $this->callApiAs($this->programmer, 'GET', '/api/admin/curation-types')
            ->assertOk()
            ->assertJsonFragment(['id' => $type->id, 'name' => 'Admin Listed Type']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function authorized_user_can_create_a_curation_type(): void
    {
        $this->actingAs($this->programmer, 'api')->postJson('/api/admin/curation-types', [
            'name' => 'New Admin Type',
            'description' => 'Created through the administration API.',
        ])->assertCreated()->assertJsonFragment(['name' => 'New Admin Type']);

        $this->assertDatabaseHas('curation_types', [
            'name' => 'New Admin Type',
            'description' => 'Created through the administration API.',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function create_validates_name_and_uniqueness(): void
    {
        factory(CurationType::class)->create(['name' => 'Existing Admin Type']);

        $this->actingAs($this->programmer, 'api')->postJson('/api/admin/curation-types', [
            'name' => 'Existing Admin Type',
        ])->assertStatus(422)->assertJsonValidationErrors('name');

        $this->actingAs($this->programmer, 'api')->postJson('/api/admin/curation-types', [
            'name' => 'tiny',
        ])->assertStatus(422)->assertJsonValidationErrors('name');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function authorized_user_can_update_a_curation_type(): void
    {
        $type = factory(CurationType::class)->create(['name' => 'Type Before Update']);

        $this->actingAs($this->programmer, 'api')->putJson("/api/admin/curation-types/{$type->id}", [
            'name' => 'Type After Update',
            'description' => 'Updated description.',
        ])->assertOk()->assertJsonFragment(['name' => 'Type After Update']);

        $this->assertDatabaseHas('curation_types', [
            'id' => $type->id,
            'name' => 'Type After Update',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function update_uniqueness_ignores_the_current_curation_type(): void
    {
        $type = factory(CurationType::class)->create(['name' => 'Unchanged Type Name']);

        $this->actingAs($this->programmer, 'api')->putJson("/api/admin/curation-types/{$type->id}", [
            'name' => 'Unchanged Type Name',
            'description' => 'Only the description changed.',
        ])->assertOk()->assertJsonFragment(['description' => 'Only the description changed.']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function authorized_user_can_delete_an_unused_curation_type(): void
    {
        $type = factory(CurationType::class)->create(['name' => 'Unused Admin Type']);

        $this->callApiAs($this->programmer, 'DELETE', "/api/admin/curation-types/{$type->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('curation_types', ['id' => $type->id]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function referenced_curation_type_cannot_be_deleted(): void
    {
        $type = factory(CurationType::class)->create(['name' => 'Referenced Admin Type']);
        factory(Curation::class)->create(['curation_type_id' => $type->id]);

        $this->callApiAs($this->programmer, 'DELETE', "/api/admin/curation-types/{$type->id}")
            ->assertStatus(409)
            ->assertJson(['message' => 'This curation type is in use and cannot be deleted.']);

        $this->assertDatabaseHas('curation_types', ['id' => $type->id]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function users_without_the_admin_role_or_operation_permission_are_forbidden(): void
    {
        $viewer = factory(User::class)->create();
        $adminWithoutPermission = factory(User::class)->create();
        $adminWithoutPermission->assignRole(
            Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web'])
        );

        $this->callApiAs($viewer, 'GET', '/api/admin/curation-types')->assertForbidden();
        $this->callApiAs($adminWithoutPermission, 'GET', '/api/admin/curation-types')->assertForbidden();
    }
}
