<?php

namespace Tests\Unit\Http\Controllers\Api;

use App\Affiliation;
use App\AffiliationType;
use App\ExpertPanel;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

#[\PHPUnit\Framework\Attributes\Group('api')]
#[\PHPUnit\Framework\Attributes\Group('admin-affiliations')]
class AffiliationAdminControllerTest extends TestCase
{
    use DatabaseTransactions;

    private User $programmer;

    public function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'programmer', 'guard_name' => 'web']);
        $this->programmer = factory(User::class)->create();
        $this->programmer->assignRole($role);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_list_identity_hierarchy_and_linked_panel_information(): void
    {
        $type = $this->type('affiliation-test-type');
        $parent = $this->affiliation(91001, 'Parent Affiliation', $type);
        $child = $this->affiliation(91002, 'Child Affiliation', $type, [
            'short_name' => 'Child',
            'parent_id' => $parent->id,
        ]);
        $panel = factory(ExpertPanel::class)->create(['name' => 'Linked Expert Panel', 'affiliation_id' => $child->id]);

        $response = $this->actingAs($this->programmer, 'api')->getJson('/api/admin/affiliations?per_page=100')->assertOk();
        $response->assertJsonStructure(['data', 'current_page', 'per_page', 'total']);
        $listed = collect($response->json('data'))->firstWhere('id', $child->id);

        $this->assertSame('Child Affiliation', $listed['name']);
        $this->assertSame(91002, $listed['clingen_id']);
        $this->assertSame('affiliation-test-type', $listed['type']['name']);
        $this->assertSame($parent->id, $listed['parent']['id']);
        $this->assertSame($panel->id, $listed['expert_panel']['id']);
        $this->assertSame(1, $listed['expert_panel_count']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function affiliation_listing_paginates_and_bounds_the_page_size(): void
    {
        $type = $this->type('pagination-type');
        $this->affiliation(96001, 'Pagination One', $type);
        $this->affiliation(96002, 'Pagination Two', $type);

        $this->actingAs($this->programmer, 'api')->getJson('/api/admin/affiliations?per_page=1')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('per_page', 1);
        $this->actingAs($this->programmer, 'api')->getJson('/api/admin/affiliations?per_page=1000')
            ->assertOk()->assertJsonPath('per_page', 100);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_update_only_the_local_short_name(): void
    {
        $type = $this->type('immutable-affiliation-type');
        $parent = $this->affiliation(92001, 'Original Parent', $type);
        $otherParent = $this->affiliation(92002, 'Other Parent', $type);
        $affiliation = $this->affiliation(92003, 'Canonical Name', $type, ['parent_id' => $parent->id]);

        $this->actingAs($this->programmer, 'api')->putJson("/api/admin/affiliations/{$affiliation->id}", [
            'short_name' => 'Local Name',
            'name' => 'Changed Name',
            'clingen_id' => 99999,
            'affiliation_type_id' => $this->type('other-type')->id,
            'type_id' => $this->type('legacy-type')->id,
            'parent_id' => $otherParent->id,
        ])->assertOk()->assertJsonFragment([
            'id' => $affiliation->id,
            'name' => 'Canonical Name',
            'short_name' => 'Local Name',
            'clingen_id' => 92003,
            'parent_id' => $parent->id,
        ]);

        $this->assertDatabaseHas('affiliations', [
            'id' => $affiliation->id,
            'name' => 'Canonical Name',
            'short_name' => 'Local Name',
            'clingen_id' => 92003,
            'affiliation_type_id' => $type->id,
            'parent_id' => $parent->id,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function short_name_is_nullable_length_limited_and_unique_within_type(): void
    {
        $firstType = $this->type('first-unique-type');
        $secondType = $this->type('second-unique-type');
        $existing = $this->affiliation(93001, 'Existing Affiliation', $firstType, ['short_name' => 'Shared']);
        $sameType = $this->affiliation(93002, 'Same Type Affiliation', $firstType);
        $otherType = $this->affiliation(93003, 'Other Type Affiliation', $secondType);

        $this->actingAs($this->programmer, 'api')->putJson("/api/admin/affiliations/{$sameType->id}", [
            'short_name' => 'Shared',
        ])->assertStatus(422)->assertJsonValidationErrors('short_name');
        $this->actingAs($this->programmer, 'api')->putJson("/api/admin/affiliations/{$sameType->id}", [
            'short_name' => 'MoreThanFifteenCharacters',
        ])->assertStatus(422)->assertJsonValidationErrors('short_name');
        $this->actingAs($this->programmer, 'api')->putJson("/api/admin/affiliations/{$otherType->id}", [
            'short_name' => $existing->short_name,
        ])->assertOk();
        $this->actingAs($this->programmer, 'api')->putJson("/api/admin/affiliations/{$sameType->id}", [
            'short_name' => null,
        ])->assertOk();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function non_admin_users_cannot_list_or_update_affiliations(): void
    {
        $affiliation = $this->affiliation(94001, 'Protected Affiliation', $this->type('protected-type'));
        $viewer = factory(User::class)->create();

        $this->actingAs($viewer, 'api')->getJson('/api/admin/affiliations')->assertForbidden();
        $this->actingAs($viewer, 'api')->putJson("/api/admin/affiliations/{$affiliation->id}", [
            'short_name' => 'Forbidden',
        ])->assertForbidden();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function create_and_delete_admin_routes_do_not_exist(): void
    {
        $affiliation = $this->affiliation(95001, 'Permanent Affiliation', $this->type('permanent-type'));

        $this->actingAs($this->programmer, 'api')->postJson('/api/admin/affiliations', [
            'name' => 'Unsupported Affiliation',
        ])->assertStatus(405);
        $this->actingAs($this->programmer, 'api')->deleteJson("/api/admin/affiliations/{$affiliation->id}")
            ->assertStatus(405);
        $this->assertDatabaseHas('affiliations', ['id' => $affiliation->id, 'deleted_at' => null]);
    }

    private function type(string $name): AffiliationType
    {
        return AffiliationType::firstOrCreate(['name' => $name]);
    }

    private function affiliation(int $clingenId, string $name, AffiliationType $type, array $attributes = []): Affiliation
    {
        return Affiliation::create(array_merge([
            'clingen_id' => $clingenId,
            'name' => $name,
            'short_name' => null,
            'affiliation_type_id' => $type->id,
            'parent_id' => null,
        ], $attributes));
    }
}
