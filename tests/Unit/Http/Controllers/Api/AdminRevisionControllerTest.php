<?php

namespace Tests\Unit\Http\Controllers\Api;

use App\Affiliation;
use App\ExpertPanel;
use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminRevisionControllerTest extends TestCase
{
    public static function entities(): array
    {
        return [
            'users' => [User::class, 'users', 'list users'],
            'expert panels' => [ExpertPanel::class, 'expert-panels', 'list expert-panels'],
            'affiliations' => [Affiliation::class, 'affiliations', null],
        ];
    }

    private function actor(string $roleName = 'programmer', ?string $permission = null): User
    {
        Notification::fake();
        $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        $role->syncPermissions($permission ? [Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web'])] : []);
        $user = factory(User::class)->create(['name' => 'Revision Administrator']);
        $user->assignRole($role);

        return $user;
    }

    private function record(string $class)
    {
        return factory($class)->create(array_merge(
            ['name' => 'Original revision name'],
            $class === Affiliation::class ? ['clingen_id' => 987654] : [],
        ));
    }

    #[Test]
    #[DataProvider('entities')]
    public function revisions_are_still_written_and_returned_for_each_entity($class, $path, $permission): void
    {
        $actor = $this->actor('programmer', $permission);
        $this->actingAs($actor, 'api');
        $target = $this->record($class);
        $this->assertDatabaseHas('revisions', [
            'revisionable_type' => $class, 'revisionable_id' => $target->id, 'key' => 'created_at',
        ]);
        $target->update(['name' => 'Changed revision name']);
        $this->assertDatabaseHas('revisions', [
            'revisionable_type' => $class, 'revisionable_id' => $target->id,
            'key' => 'name', 'old_value' => 'Original revision name',
            'new_value' => 'Changed revision name', 'user_id' => $actor->id,
        ]);

        $response = $this->getJson("/api/admin/{$path}/{$target->id}/revisions")->assertOk();
        $change = collect($response->json('data'))->firstWhere('field', 'Name');
        $this->assertSame('Original revision name', $change['old_value']);
        $this->assertSame('Changed revision name', $change['new_value']);
        $this->assertSame($actor->name, $change['changed_by']);
        $this->assertNotNull($change['changed_at']);
        $this->assertSame(['id', 'field', 'old_value', 'new_value', 'changed_by', 'changed_at'], array_keys($change));
    }

    #[Test]
    #[DataProvider('entities')]
    public function shell_access_is_required_even_with_entity_permission($class, $path, $permission): void
    {
        $viewer = $this->actor('viewer', $permission);
        $target = $this->record($class);
        $this->actingAs($viewer, 'api')->getJson("/api/admin/{$path}/{$target->id}/revisions")->assertForbidden();
    }

    #[Test]
    public function shell_roles_without_list_permission_cannot_read_users_or_panels(): void
    {
        foreach (['admin', 'programmer'] as $role) {
            $this->actingAs($this->actor($role), 'api');
            $user = factory(User::class)->create();
            $panel = factory(ExpertPanel::class)->create();
            $this->getJson("/api/admin/users/{$user->id}/revisions")->assertForbidden();
            $this->getJson("/api/admin/expert-panels/{$panel->id}/revisions")->assertForbidden();
        }
    }

    #[Test]
    #[DataProvider('entities')]
    public function empty_history_and_missing_records_are_handled($class, $path, $permission): void
    {
        $this->actingAs($this->actor('admin', $permission), 'api');
        $target = $this->record($class);
        $target->revisionHistory()->delete();
        $this->getJson("/api/admin/{$path}/{$target->id}/revisions")->assertOk()->assertExactJson(['data' => []]);
        $this->getJson("/api/admin/{$path}/999999999/revisions")->assertNotFound();
        $this->postJson("/api/admin/{$path}/{$target->id}/revisions", [])->assertStatus(405);
    }

    #[Test]
    public function ordering_nulls_actor_fallbacks_and_safe_fields_are_preserved(): void
    {
        $actor = $this->actor('admin', 'list users');
        $target = factory(User::class)->create();
        $target->revisionHistory()->delete();
        $other = factory(User::class)->create();
        $other->update(['name' => 'Unrelated change']);

        foreach ([
            ['name', null, 'First', '2026-01-01 12:00:00', null],
            ['name', 'Second', '<b>Third</b>', '2026-01-02 12:00:00', $actor->id],
            ['affiliation_id', '21', '22', '2026-01-02 12:00:00', 999999999],
            ['name', 'First', 'Second', '2026-01-01 13:00:00', null],
            ['password', 'secret hash', 'new secret hash', '2026-01-03 12:00:00', null],
            ['remember_token', null, 'secret token', '2026-01-03 12:00:00', null],
            ['internal_payload', null, '{"secret":true}', '2026-01-03 12:00:00', null],
        ] as [$key, $old, $new, $date, $userId]) {
            DB::table('revisions')->insert([
                'revisionable_type' => User::class, 'revisionable_id' => $target->id,
                'key' => $key, 'old_value' => $old, 'new_value' => $new,
                'created_at' => $date, 'updated_at' => $date, 'user_id' => $userId,
            ]);
        }
        $actor->delete();
        $reader = $this->actor('programmer', 'list users');
        $response = $this->actingAs($reader, 'api')->getJson("/api/admin/users/{$target->id}/revisions")
            ->assertOk()->assertJsonCount(4, 'data');
        $this->assertSame(['22', '<b>Third</b>', 'Second', 'First'], array_column($response->json('data'), 'new_value'));
        $response->assertJsonPath('data.0.field', 'Affiliation ID')
            ->assertJsonPath('data.0.changed_by', 'Unknown user (ID 999999999)')
            ->assertJsonPath('data.1.changed_by', 'Revision Administrator')
            ->assertJsonPath('data.3.old_value', null)
            ->assertJsonPath('data.3.changed_by', 'System / unknown');
    }
}
