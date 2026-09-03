<?php

namespace Tests\Unit\Http\Controllers\Api;

use App\ModeOfInheritance;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

#[\PHPUnit\Framework\Attributes\Group('api')]
#[\PHPUnit\Framework\Attributes\Group('admin-mois')]
class MoiAdminControllerTest extends TestCase
{
    use DatabaseTransactions;

    private User $programmer;

    public function setUp(): void
    {
        parent::setUp();

        $permissions = collect(['list mois', 'update mois'])->map(fn ($name) => Permission::firstOrCreate([
            'name' => $name,
            'guard_name' => 'web',
        ]));
        $role = Role::firstOrCreate(['name' => 'programmer', 'guard_name' => 'web']);
        $role->givePermissionTo($permissions);
        $this->programmer = factory(User::class)->create();
        $this->programmer->assignRole($role);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function authorized_user_can_list_all_mois_with_parent_information(): void
    {
        $parent = $this->createMoi('HP:9900001', 'Parent MOI');
        $child = $this->createMoi('HP:9900002', 'Child MOI', ['parent_id' => $parent->id]);

        $this->actingAs($this->programmer, 'api')->getJson('/api/admin/mois')
            ->assertOk()
            ->assertJsonFragment(['id' => $child->id, 'hp_id' => 'HP:9900002'])
            ->assertJsonFragment(['id' => $parent->id, 'name' => 'Parent MOI']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function authorized_user_can_update_only_the_curatable_flag(): void
    {
        $moi = $this->createMoi('HP:9900003', 'Canonical Name', [
            'abbreviation' => 'CAN',
            'curatable' => false,
        ]);

        $this->actingAs($this->programmer, 'api')->putJson("/api/admin/mois/{$moi->id}", [
            'curatable' => true,
            'name' => 'Changed Name',
            'abbreviation' => 'BAD',
            'hp_id' => 'HP:9999999',
            'parent_id' => null,
        ])->assertOk()->assertJsonFragment([
            'id' => $moi->id,
            'name' => 'Canonical Name',
            'abbreviation' => 'CAN',
            'hp_id' => 'HP:9900003',
            'curatable' => 1,
        ]);

        $this->assertDatabaseHas('mode_of_inheritances', [
            'id' => $moi->id,
            'name' => 'Canonical Name',
            'abbreviation' => 'CAN',
            'hp_id' => 'HP:9900003',
            'curatable' => 1,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function curatable_update_is_validated(): void
    {
        $moi = $this->createMoi('HP:9900004', 'Validated MOI');

        $this->actingAs($this->programmer, 'api')->putJson("/api/admin/mois/{$moi->id}", [])
            ->assertStatus(422)->assertJsonValidationErrors('curatable');
        $this->actingAs($this->programmer, 'api')->putJson("/api/admin/mois/{$moi->id}", [
            'curatable' => 'sometimes',
        ])->assertStatus(422)->assertJsonValidationErrors('curatable');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_api_rejects_missing_role_or_operation_permission(): void
    {
        $moi = $this->createMoi('HP:9900005', 'Protected MOI');
        $viewer = factory(User::class)->create();
        $admin = factory(User::class)->create();
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $adminRole->revokePermissionTo('update mois');
        $admin->assignRole($adminRole);

        $this->actingAs($viewer, 'api')->getJson('/api/admin/mois')->assertForbidden();
        $this->actingAs($admin, 'api')->putJson("/api/admin/mois/{$moi->id}", ['curatable' => true])
            ->assertForbidden();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function create_and_delete_admin_routes_do_not_exist(): void
    {
        $moi = $this->createMoi('HP:9900006', 'Permanent MOI');

        $this->actingAs($this->programmer, 'api')->postJson('/api/admin/mois', [
            'name' => 'Unsupported MOI',
        ])->assertStatus(405);
        $this->actingAs($this->programmer, 'api')->deleteJson("/api/admin/mois/{$moi->id}")
            ->assertStatus(405);
    }

    private function createMoi(string $hpId, string $name, array $attributes = []): ModeOfInheritance
    {
        $moi = new ModeOfInheritance();
        $moi->forceFill(array_merge([
            'name' => $name,
            'abbreviation' => null,
            'hp_id' => $hpId,
            'hp_uri' => 'http://purl.obolibrary.org/obo/'.str_replace(':', '_', $hpId),
            'parent_id' => null,
            'curatable' => false,
        ], $attributes))->save();

        return $moi;
    }
}
