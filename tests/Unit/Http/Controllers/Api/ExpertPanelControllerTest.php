<?php

namespace Tests\Unit\Http\Controllers\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group expert-panels
 * @group panels
 * @group api
 */
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
                        ->call('GET', '/api/expert-panels')
                        ->assertStatus(200);

        $this->assertEquals($this->panels->toArray(), $response->original->toArray());
    }

    /**
     * @test
     */
    public function index_includes_users_when_requested()
    {
        \Artisan::call('db:seed', ['--class'=>'RolesAndPermissionsSeeder']);
        $this->user->assignRole('curator');
        $this->panels->first()->users()->attach($this->user->id);

        $response = $this->actingAs($this->user, 'api')
                        ->call('GET', '/api/expert-panels?with=users')
                        ->assertStatus(200)
                        ->assertSee('"users":');
    }
}
