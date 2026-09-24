<?php

namespace Tests\Unit\Http\Controllers\Api;

use App\ExpertPanel;
use App\User;
use App\Notifications\Users\Welcome;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserAdminCreationTest extends TestCase
{
    private array $payload;

    public function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $admin = factory(User::class)->create();
        $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $role->givePermissionTo(Permission::firstOrCreate(['name' => 'create users', 'guard_name' => 'web']));
        $admin->assignRole($role);
        $this->actingAs($admin, 'api');
        $viewer = Role::firstOrCreate(['name' => 'viewer', 'guard_name' => 'web']);
        $permission = Permission::firstOrCreate(['name' => 'list curations', 'guard_name' => 'web']);
        $panel = factory(ExpertPanel::class)->create();
        $this->payload = [
            'name' => 'Safe Onboarding User', 'email' => 'safe-onboarding@example.com',
            'role_ids' => [$viewer->id], 'permission_ids' => [$permission->id],
            'expert_panels' => [['id' => $panel->id, 'is_curator' => true, 'is_coordinator' => false, 'can_edit_curations' => true]],
        ];
        Notification::fake();
    }

    public function test_create_persists_associations_uses_hashed_random_credential_and_sends_one_welcome(): void
    {
        // Verify the raw input at the hashing boundary without returning/logging it.
        $hasher = Hash::getFacadeRoot();
        $proxy = \Mockery::mock($hasher);
        $proxy->shouldReceive('make')->once()->withArgs(fn ($value) => strlen($value) === 64 && ctype_xdigit($value))->passthru();
        Hash::swap($proxy);
        $response = $this->postJson('/api/admin/users', $this->payload + ['password' => 'tester', 'deactivated_at' => now()])->assertCreated();
        Hash::swap($hasher);
        $user = User::findOrFail($response->json('id'));
        $this->assertFalse(Hash::check('tester', $user->password));
        $this->assertFalse(Hash::needsRehash($user->password));
        $response->assertJsonMissingPath('password')->assertJsonMissingPath('remember_token')->assertJsonPath('deactivated_at', null);
        $this->assertSame($this->payload['role_ids'], $user->roles->modelKeys());
        $this->assertSame($this->payload['permission_ids'], $user->permissions->modelKeys());
        $this->assertDatabaseHas('expert_panel_user', ['user_id' => $user->id, 'expert_panel_id' => $this->payload['expert_panels'][0]['id'], 'is_curator' => 1, 'is_coordinator' => 0, 'can_edit_curations' => 1]);
        Notification::assertSentToTimes($user, Welcome::class, 1);
        Notification::assertCount(1);
        $welcome = (new Welcome())->toMail($user);
        $this->assertSame('email.welcome', $welcome->view);
        $this->assertStringContainsString(route('password.request'), view($welcome->view)->render());
    }

    public function test_restricted_user_and_admin_without_create_permission_cannot_create(): void
    {
        $restricted = factory(User::class)->create();
        $this->actingAs($restricted, 'api')->postJson('/api/admin/users', $this->payload)->assertForbidden();
        $role = Role::firstOrCreate(['name' => 'programmer', 'guard_name' => 'web']);
        $role->syncPermissions([]);
        $restricted->assignRole($role);
        $this->actingAs($restricted->fresh(), 'api')->postJson('/api/admin/users', $this->payload)->assertForbidden();
        $this->assertDatabaseMissing('users', ['email' => $this->payload['email']]);
    }

    public function test_creation_validates_identity_and_associations_without_sending_welcome(): void
    {
        $existing = factory(User::class)->create();
        Notification::fake();
        $invalid = array_merge($this->payload, ['name' => '', 'email' => $existing->email, 'role_ids' => [99999999], 'permission_ids' => [99999999], 'expert_panels' => [array_merge($this->payload['expert_panels'][0], ['id' => 99999999])]]);
        $this->postJson('/api/admin/users', $invalid)->assertUnprocessable()->assertJsonValidationErrors(['name', 'email', 'role_ids.0', 'permission_ids.0', 'expert_panels.0.id']);
        $this->postJson('/api/admin/users', array_merge($this->payload, ['email' => 'invalid']))->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->postJson('/api/admin/users', array_merge($this->payload, ['expert_panels' => [$this->payload['expert_panels'][0], $this->payload['expert_panels'][0]]]))->assertUnprocessable()->assertJsonValidationErrors('expert_panels.0.id');
        $this->assertDatabaseMissing('users', ['email' => $this->payload['email']]);
        Notification::assertNothingSent();
    }

    public function test_association_failure_rolls_back_user_roles_permissions_and_welcome(): void
    {
        $createdId = null;
        DB::connection()->beforeExecuting(function ($query) use (&$createdId) {
            if (str_starts_with($query, 'insert into `expert_panel_user`')) {
                $createdId = User::where('email', $this->payload['email'])->value('id');
                throw new \RuntimeException('Simulated membership storage failure');
            }
        });
        $this->withoutExceptionHandling();
        try {
            $this->postJson('/api/admin/users', $this->payload);
            $this->fail('Expected storage failure');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Simulated membership storage failure', $exception->getMessage());
        }
        $this->assertNotNull($createdId);
        $this->assertDatabaseMissing('users', ['id' => $createdId]);
        $this->assertDatabaseMissing('model_has_roles', ['model_id' => $createdId, 'model_type' => User::class]);
        $this->assertDatabaseMissing('model_has_permissions', ['model_id' => $createdId, 'model_type' => User::class]);
        $this->assertDatabaseMissing('expert_panel_user', ['user_id' => $createdId]);
        Notification::assertNothingSent();
    }

    public function test_new_user_can_request_and_use_existing_password_reset_flow(): void
    {
        $response = $this->postJson('/api/admin/users', $this->payload)->assertCreated();
        $user = User::findOrFail($response->json('id'));
        auth()->shouldUse('web');
        $this->postJson('/password/email', ['email' => $user->email])->assertOk();
        $notice = Notification::sent($user, ResetPassword::class)->sole();
        $this->postJson('/password/reset', [
            'email' => $user->email, 'token' => $notice->token,
            'password' => 'UserChosen!Password73', 'password_confirmation' => 'UserChosen!Password73',
        ])->assertOk();
        $this->assertTrue(Hash::check('UserChosen!Password73', $user->fresh()->password));
        $this->assertAuthenticatedAs($user, 'web');
        Notification::assertSentToTimes($user, Welcome::class, 1);
    }
}
