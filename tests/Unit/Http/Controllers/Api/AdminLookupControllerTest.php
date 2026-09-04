<?php

namespace Tests\Unit\Http\Controllers\Api;

use App\Curation;
use App\CurationStatus;
use App\Rationale;
use App\Upload;
use App\UploadCategory;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

#[\PHPUnit\Framework\Attributes\Group('api')]
#[\PHPUnit\Framework\Attributes\Group('admin-lookups')]
class AdminLookupControllerTest extends TestCase
{
    use DatabaseTransactions;

    private User $programmer;

    public function setUp(): void
    {
        parent::setUp();

        $permissions = collect(['rationales', 'curation-statuses'])->flatMap(
            fn ($domain) => collect(['list', 'create', 'update', 'delete'])->map(
                fn ($operation) => Permission::firstOrCreate([
                    'name' => "{$operation} {$domain}",
                    'guard_name' => 'web',
                ])
            )
        );
        $role = Role::firstOrCreate(['name' => 'programmer', 'guard_name' => 'web']);
        $role->givePermissionTo($permissions);
        $this->programmer = factory(User::class)->create();
        $this->programmer->assignRole($role);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function rationale_admin_crud_contract_is_authorized_and_validated(): void
    {
        $listed = factory(Rationale::class)->create(['name' => 'Listed Rationale']);
        $this->actingAs($this->programmer, 'api')->getJson('/api/admin/rationales')
            ->assertOk()->assertJsonFragment(['id' => $listed->id]);

        $this->actingAs($this->programmer, 'api')->postJson('/api/admin/rationales', [])
            ->assertStatus(422)->assertJsonValidationErrors('name');
        $created = $this->actingAs($this->programmer, 'api')->postJson('/api/admin/rationales', [
            'name' => 'Created Rationale',
        ])->assertCreated()->json();
        $this->actingAs($this->programmer, 'api')->putJson("/api/admin/rationales/{$created['id']}", [
            'name' => 'Updated Rationale',
        ])->assertOk()->assertJsonFragment(['name' => 'Updated Rationale']);
        $this->actingAs($this->programmer, 'api')->deleteJson("/api/admin/rationales/{$created['id']}")
            ->assertNoContent();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function referenced_rationale_cannot_be_deleted(): void
    {
        $rationale = factory(Rationale::class)->create();
        $curation = factory(Curation::class)->create();
        $curation->rationales()->attach($rationale);

        $this->actingAs($this->programmer, 'api')->deleteJson("/api/admin/rationales/{$rationale->id}")
            ->assertStatus(409)->assertJson(['message' => 'This rationale is in use and cannot be deleted.']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function rationale_admin_api_rejects_missing_role_and_permission(): void
    {
        $viewer = factory(User::class)->create();
        $admin = factory(User::class)->create();
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $adminRole->revokePermissionTo('create rationales');
        $admin->assignRole($adminRole);

        $this->actingAs($viewer, 'api')->getJson('/api/admin/rationales')->assertForbidden();
        $this->actingAs($admin, 'api')->postJson('/api/admin/rationales', ['name' => 'Forbidden Rationale'])
            ->assertForbidden();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function curation_status_admin_crud_contract_is_authorized_and_validated(): void
    {
        $listed = factory(CurationStatus::class)->create(['name' => 'Listed Status']);
        $this->actingAs($this->programmer, 'api')->getJson('/api/admin/curation-statuses')
            ->assertOk()->assertJsonFragment(['id' => $listed->id]);
        $this->actingAs($this->programmer, 'api')->postJson('/api/admin/curation-statuses', [])
            ->assertStatus(422)->assertJsonValidationErrors('name');
        $created = $this->actingAs($this->programmer, 'api')->postJson('/api/admin/curation-statuses', [
            'name' => 'Created Status', 'description' => 'Created for testing.',
        ])->assertCreated()->json();
        $this->actingAs($this->programmer, 'api')->putJson("/api/admin/curation-statuses/{$created['id']}", [
            'name' => 'Updated Status', 'description' => 'Updated for testing.',
        ])->assertOk()->assertJsonFragment(['description' => 'Updated for testing.']);
        $this->actingAs($this->programmer, 'api')->deleteJson("/api/admin/curation-statuses/{$created['id']}")
            ->assertNoContent();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function referenced_curation_status_cannot_be_deleted(): void
    {
        $status = factory(CurationStatus::class)->create();
        $curation = factory(Curation::class)->create();
        $curation->updateQuietly(['curation_status_id' => $status->id]);

        $this->actingAs($this->programmer, 'api')->deleteJson("/api/admin/curation-statuses/{$status->id}")
            ->assertStatus(409)->assertJson(['message' => 'This curation status is in use and cannot be deleted.']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function curation_status_admin_api_rejects_missing_role_and_permission(): void
    {
        $viewer = factory(User::class)->create();
        $admin = factory(User::class)->create();
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $adminRole->revokePermissionTo('create curation-statuses');
        $admin->assignRole($adminRole);

        $this->actingAs($viewer, 'api')->getJson('/api/admin/curation-statuses')->assertForbidden();
        $this->actingAs($admin, 'api')->postJson('/api/admin/curation-statuses', ['name' => 'Forbidden Status'])
            ->assertForbidden();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function upload_category_admin_crud_contract_uses_privileged_roles_and_validates_name(): void
    {
        $listed = UploadCategory::create(['name' => 'Listed Upload Category']);
        $this->actingAs($this->programmer, 'api')->getJson('/api/admin/upload-categories')
            ->assertOk()->assertJsonFragment(['id' => $listed->id]);
        $this->actingAs($this->programmer, 'api')->postJson('/api/admin/upload-categories', [])
            ->assertStatus(422)->assertJsonValidationErrors('name');
        $created = $this->actingAs($this->programmer, 'api')->postJson('/api/admin/upload-categories', [
            'name' => 'Created Upload Category',
        ])->assertCreated()->json();
        $this->actingAs($this->programmer, 'api')->putJson("/api/admin/upload-categories/{$created['id']}", [
            'name' => 'Updated Upload Category',
        ])->assertOk()->assertJsonFragment(['name' => 'Updated Upload Category']);
        $this->actingAs($this->programmer, 'api')->deleteJson("/api/admin/upload-categories/{$created['id']}")
            ->assertNoContent();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function referenced_upload_category_cannot_be_deleted(): void
    {
        $category = UploadCategory::create(['name' => 'Referenced Upload Category']);
        $curation = factory(Curation::class)->create();
        Upload::create([
            'curation_id' => $curation->id,
            'upload_category_id' => $category->id,
            'name' => 'Referenced upload',
            'file_name' => 'referenced.txt',
            'file_path' => 'testing/referenced.txt',
        ]);

        $this->actingAs($this->programmer, 'api')->deleteJson("/api/admin/upload-categories/{$category->id}")
            ->assertStatus(409)->assertJson(['message' => 'This upload category is in use and cannot be deleted.']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function upload_category_admin_api_rejects_non_privileged_users(): void
    {
        $viewer = factory(User::class)->create();

        $this->actingAs($viewer, 'api')->getJson('/api/admin/upload-categories')->assertForbidden();
        $this->actingAs($viewer, 'api')->postJson('/api/admin/upload-categories', ['name' => 'Forbidden Category'])
            ->assertForbidden();
    }
}
