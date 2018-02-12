<?php

namespace Tests\Feature\Controllers\Api;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ExpertPanelControllerTest extends TestCase
{
    use RefreshDatabase;

    public function setUp()
    {
        parent::setUp();
        $this->panels = factory(\App\ExpertPanel::class, 10)->create();
        $this->user = factory(\App\User::class)->create();
    }

    /**
     * @test
     */
    public function lists_all_expert_panels()
    {
        $response = $this->actingAs($this->user, 'api')
                        ->call('GET', '/api/expert-panels');

        $response
            ->assertStatus(200)
            ->assertExactJson($this->panels->toArray());
    }
}
