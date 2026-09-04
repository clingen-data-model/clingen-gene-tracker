<?php

namespace Tests\Unit\Http\Controllers\Api;

use App\Email;
use App\Notification;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

#[\PHPUnit\Framework\Attributes\Group('api')]
#[\PHPUnit\Framework\Attributes\Group('admin-logs')]
class EmailNotificationAdminControllerTest extends TestCase
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
    public function authorized_user_can_list_and_inspect_emails_newest_first(): void
    {
        $older = Email::create(['from' => ['old@example.com' => 'Old'], 'to' => ['to@example.com' => null], 'subject' => 'Older', 'body' => '<b>old</b>']);
        $newer = Email::create(['from' => ['new@example.com' => 'New'], 'to' => ['to@example.com' => 'Recipient'], 'subject' => 'Newer', 'body' => '<script>alert(1)</script>']);
        $older->timestamps = false;
        $older->update(['created_at' => now()->subDay()]);

        $response = $this->actingAs($this->programmer, 'api')->getJson('/api/admin/emails?per_page=1')->assertOk();
        $response->assertJsonPath('per_page', 1)->assertJsonPath('data.0.id', $newer->id);
        $detail = $this->actingAs($this->programmer, 'api')->getJson("/api/admin/emails/{$newer->id}")
            ->assertOk()->assertJsonPath('body', '<script>alert(1)</script>');
        $this->assertSame('New', $detail->json('from')['new@example.com']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function email_log_is_read_only_and_shell_role_is_required(): void
    {
        $email = Email::create(['from' => ['a@example.com' => null], 'to' => ['b@example.com' => null]]);
        $viewer = factory(User::class)->create();
        $this->actingAs($viewer, 'api')->getJson('/api/admin/emails')->assertForbidden();
        $this->actingAs($this->programmer, 'api')->postJson('/api/admin/emails', [])->assertStatus(405);
        $this->actingAs($this->programmer, 'api')->putJson("/api/admin/emails/{$email->id}", [])->assertStatus(405);
        $this->actingAs($this->programmer, 'api')->deleteJson("/api/admin/emails/{$email->id}")->assertStatus(405);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function authorized_user_can_list_inspect_and_delete_notifications_without_marking_them_read(): void
    {
        $recipient = factory(User::class)->create(['name' => 'Notification Recipient']);
        $id = (string) Str::uuid();
        DB::table('notifications')->insert([
            'id' => $id, 'type' => 'App\\Notifications\\ExampleNotice',
            'notifiable_type' => User::class, 'notifiable_id' => $recipient->id,
            'data' => json_encode(['message' => 'Deterministic payload']), 'read_at' => null,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs($this->programmer, 'api')->getJson('/api/admin/notifications?per_page=1000')
            ->assertOk()->assertJsonPath('per_page', 100)
            ->assertJsonFragment(['id' => $id, 'readable_type' => 'ExampleNotice']);
        $this->actingAs($this->programmer, 'api')->getJson("/api/admin/notifications/{$id}")
            ->assertOk()->assertJsonPath('recipient.name', 'Notification Recipient')
            ->assertJsonPath('data.message', 'Deterministic payload');
        $this->assertNull(Notification::findOrFail($id)->read_at);

        $this->actingAs($this->programmer, 'api')->deleteJson("/api/admin/notifications/{$id}")->assertNoContent();
        $this->assertDatabaseMissing('notifications', ['id' => $id]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function notification_creation_and_update_are_not_admin_operations_and_shell_role_is_required(): void
    {
        $viewer = factory(User::class)->create();
        $this->actingAs($viewer, 'api')->getJson('/api/admin/notifications')->assertForbidden();
        $this->actingAs($this->programmer, 'api')->postJson('/api/admin/notifications', [])->assertStatus(405);
        $this->actingAs($this->programmer, 'api')->putJson('/api/admin/notifications/missing', [])->assertStatus(405);
    }
}
