<?php

namespace Tests\Unit\Http\Controllers\Api;

use App\ApiClient;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

#[\PHPUnit\Framework\Attributes\Group('api')]
#[\PHPUnit\Framework\Attributes\Group('admin-api-clients')]
class ApiClientAdminControllerTest extends TestCase
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
    public function authorized_user_can_list_create_inspect_and_update_clients(): void
    {
        $existing = ApiClient::factory()->create();
        $this->actingAs($this->programmer, 'api')->getJson('/api/admin/api-clients')->assertOk()->assertJsonFragment(['id' => $existing->id]);
        $created = $this->actingAs($this->programmer, 'api')->postJson('/api/admin/api-clients', ['name' => 'Test Integration', 'contact_email' => 'contact@example.com'])
            ->assertCreated()->assertJsonPath('name', 'Test Integration')->json();
        $this->assertNotEmpty($created['uuid']);
        $this->actingAs($this->programmer, 'api')->putJson("/api/admin/api-clients/{$created['id']}", ['name' => 'Updated Integration', 'contact_email' => 'updated@example.com'])
            ->assertOk()->assertJsonPath('name', 'Updated Integration');
        $this->actingAs($this->programmer, 'api')->getJson("/api/admin/api-clients/{$created['id']}")->assertOk()->assertJsonPath('uuid', $created['uuid']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function validation_requires_unique_name_and_valid_contact_email_while_update_ignores_itself(): void
    {
        $client = ApiClient::factory()->create(['name' => 'Unique Client']);
        $this->actingAs($this->programmer, 'api')->postJson('/api/admin/api-clients', ['name' => 'Unique Client', 'contact_email' => 'bad'])
            ->assertStatus(422)->assertJsonValidationErrors(['name', 'contact_email']);
        $this->actingAs($this->programmer, 'api')->putJson("/api/admin/api-clients/{$client->id}", ['name' => $client->name, 'contact_email' => $client->contact_email])->assertOk();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function token_secret_is_returned_only_at_creation_and_one_scoped_token_can_be_revoked(): void
    {
        $client = ApiClient::factory()->create();
        $other = ApiClient::factory()->create();
        $response = $this->actingAs($this->programmer, 'api')->postJson("/api/admin/api-clients/{$client->id}/tokens", ['name' => 'deploy-token'])->assertCreated();
        $this->assertNotEmpty($response->json('plain_text_token'));
        $tokenId = $response->json('token.id');
        $otherToken = $other->createToken('other-token')->accessToken;

        $detail = $this->actingAs($this->programmer, 'api')->getJson("/api/admin/api-clients/{$client->id}")->assertOk();
        $detail->assertJsonMissingPath('plain_text_token')->assertJsonMissingPath('tokens.0.token');
        $this->actingAs($this->programmer, 'api')->deleteJson("/api/admin/api-clients/{$client->id}/tokens/{$otherToken->id}")->assertNotFound();
        $this->actingAs($this->programmer, 'api')->deleteJson("/api/admin/api-clients/{$client->id}/tokens/{$tokenId}")->assertNoContent();
        $this->assertDatabaseHas('personal_access_tokens', ['id' => $otherToken->id]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function shell_role_is_required_and_client_delete_is_not_supported(): void
    {
        $viewer = factory(User::class)->create();
        $client = ApiClient::factory()->create();
        $this->actingAs($viewer, 'api')->getJson('/api/admin/api-clients')->assertForbidden();
        $this->actingAs($this->programmer, 'api')->deleteJson("/api/admin/api-clients/{$client->id}")->assertStatus(405);
        $this->assertDatabaseHas('api_clients', ['id' => $client->id]);
    }
}
