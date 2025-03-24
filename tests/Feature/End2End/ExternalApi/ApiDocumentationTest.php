<?php

namespace Tests\Feature\End2End\ExternalApi;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

/**
 * @group external-api
 */
class ApiDocumentationTest extends ExternalApiTest
{
    /**
     * @test
     */
    public function guest_cannot_get_docs()
    {
        $this->makeExternalApiRequestAsGuest('GET', '/api/v1/')
            ->assertStatus(401);
    }

    /**
     * @test
     */
    public function authed_client_can_get_docs()
    {
        $response = $this->makeExternalApiRequest('GET', '/api/v1/')
            ->assertStatus(200)
            ->assertSee(['openapi: 3.0.0'])
            ->assertSee(['GeneTracker API']);
    }
    
    
}
