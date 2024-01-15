<?php

namespace Tests\Unit\Http\Controllers\Api;

use App\User;
use Tests\TestCase;
use App\ExpertPanel;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;

/**
 * @group expert-panels
 * @group panels
 * @group api
 */
class ExpertPanelControllerTest extends TestCase
{
    use DatabaseTransactions;
    private $panels, $user;

    public function setUp(): void
    {
        parent::setUp();
        $this->panels = factory(ExpertPanel::class, 10)->create();
        $this->user = factory(User::class)->create();
    }

    /**
     * @test
     */
    public function lists_all_expert_panels()
    {
        $response = $this->actingAs($this->user, 'api')
                        ->call('GET', '/api/expert-panels')
                        ->assertStatus(200);

        // $this->assertEquals($this->panels->pluck('name', 'id')->toArray(), $response->original->pluck('name', 'id')->toArray());
    }

    /**
     * @test
     */
    public function index_includes_users_when_requested()
    {
        \Artisan::call('db:seed', ['--class'=>'RolesAndPermissionsSeeder']);
        $this->panels->first()->users()->attach($this->user->id, ['is_curator' => true]);

        $response = $this->actingAs($this->user, 'api')
                        ->call('GET', '/api/expert-panels?with=users')
                        ->assertStatus(200)
                        ->assertSee('users');
    }
}
