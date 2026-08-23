<?php

namespace Tests\Unit\Http;

use App\User;
use App\Disease;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Laravel\Passport\ClientRepository;

/**
 * @group user-vs-system-client
 */
#[\PHPUnit\Framework\Attributes\Group('user-vs-system-client')]
class UserVsSystemClientTest extends TestCase
{
    use DatabaseTransactions;

    protected string $accessToken;
    protected User $user;
    protected Disease $disease;

    public function setUp(): void
    {
        parent::setUp();

        $client = app(ClientRepository::class)->createClientCredentialsGrantClient('test-client');

        $response = $this->postJson('/oauth/token', [
            'grant_type' => 'client_credentials',
            'client_id' => $client->id,
            'client_secret' => $client->plainSecret,
            'scope' => '',
        ]);

        $response->assertOk();
        $this->accessToken = $response->json()['access_token'];
        $this->user = factory(\App\User::class)->create();
        $this->disease = factory(\App\Disease::class)->create();
    }

    protected function withSystemClient() 
    {
        return $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->accessToken,
            'Accept' => 'application/json',
        ]);
        // ->postJson("/api/{$endpoint}", $data);
    }

    /**
     * @test
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function allows_system_client_to_access_system_apis(): void
    {
        $response = $this->withSystemClient()->postJson('api/client/v1/diseases/mondos', ['mondo_ids' => [$this->disease->mondo_id]]);

        $response->assertOk();
        $response->assertJsonFragment(['mondo_id' => $this->disease->mondo_id]);
    }

    /**
     * @test
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function prevents_system_client_from_accessing_user_apis(): void
    {
        $response = $this->withSystemClient()
            ->json('GET', '/api/diseases/' . $this->disease->mondo_id);
        $response->assertUnauthorized();

    }

    /**
     * @test
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function allows_user_to_access_user_apis(): void
    {
        $this->actingAs($this->user, 'api')
            ->json('GET', '/api/diseases/' . $this->disease->mondo_id)
            ->assertOk()
            ->assertJsonFragment(['mondo_id' => $this->disease->mondo_id]);
    }
    
    /**
     * @test
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function prevents_user_from_accessing_system_apis(): void
    {
        $response = $this->actingAs($this->user, 'api')
            ->postJson('api/client/v1/diseases/mondos', ['mondo_ids' => [$this->disease->mondo_id]]);

        $response->assertUnauthorized();
    }

}
