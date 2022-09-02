<?php

namespace Tests\Feature\End2End\ExternalApi;

use App\Curation;
use App\ApiClient;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Testing\TestResponse;

class CurationShowTest extends TestCase
{
    use DatabaseTransactions;
    use WithFaker;

    private Curation $curation;
    private string $gdmUuid;
    private string $gtUuid;
    private ApiClient $client;
    private string $token;

    public function setup():void
    {
        parent::setup();
        $this->gdmUuid = $this->faker()->uuid();
        $this->gtUuid = $this->faker()->uuid();
        $this->curation = factory(Curation::class)
                    ->create([
                        'gdm_uuid' => $this->gdmUuid, 
                        'uuid' => $this->gtUuid
                    ]);

        $this->client = ApiClient::factory()->create();
        $this->token = $this->client->createToken('test')->plainTextToken;
    }

    /**
     * @test
     */
    public function can_get_precuration_by_numeric_id()
    {
        $this->makeRequest()
            ->assertStatus(200)
            ->assertJson(['data' => [
                'id' => $this->curation->id
            ]]);
    }

    /**
     * @test
     */
    public function can_get_precuration_by_uuid()
    {
        $this->makeRequest($this->curation->uuid)
            ->assertStatus(200)
            ->assertJson(['data' => [
                'id' => $this->curation->id
            ]]);
    }
    
    /**
     * @test
     */
    public function can_get_precuration_by_gdm_uuid()
    {
        $this->makeRequest($this->curation->gdm_uuid)
            ->assertStatus(200)
            ->assertJson(['data' => [
                'id' => $this->curation->id
            ]]);
    }
    
    /**
     * @test
     */
    public function responds_with_404_if_not_found()
    {
        $otherUuid = $this->faker()->uuid();
        $this->makeRequest($otherUuid)
            ->assertStatus(404);
    }
    
    
    private function makeRequest($id = null): TestResponse
    {
        $id = $id ?? $this->curation->id;

        return $this->json(
            method: 'GET', 
            uri: '/api/v1/pre-curations/'.$id, 
            data: [], 
            headers: ['Authorization' => 'Bearer '.$this->token]
        );
    }
    
}
