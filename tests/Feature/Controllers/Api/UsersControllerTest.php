<?php

namespace Tests\Feature\Controllers\Api;

use App\Http\Resources\TopicResource;
use App\User;
use Illuminate\Container\Container;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

/**
 * @group api
 * @group users
 */
class UsersControllerTest extends TestCase
{
    use RefreshDatabase;

    public function setUp()
    {
        parent::setUp();
        $this->users = factory(\App\User::class, 10)->create();
        $this->user = factory(\App\User::class)->create();
        \Artisan::call('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);
        $this->user->assignRole('programmer');
    }

    /**
     * @test
     */
    public function index_returns_200()
    {
        $this->disableExceptionHandling();
        $this->actingAs($this->user, 'api')
            ->call('GET', '/api/users')
            ->assertStatus(200);
    }

    /**
     * @test
     */
    public function index_lists_all_users()
    {
        $this->actingAs($this->user, 'api')
            ->call('GET', 'api/users')
            ->assertSee($this->users->first()->name)
            ->assertSee($this->users->last()->name);
    }

    /**
     * @test
     */
    public function filters_users_by_role()
    {
        $curators = factory(\App\User::class, 2)->create()
                        ->each(function ($user) {
                            $user->assignRole('curator');
                        });

        $this->actingAs($this->user, 'api')
            ->call('GET', 'api/users?role=curator')
            ->assertSee($curators->first()->name)
            ->assertSee($curators->last()->name)
            ->assertDontSee($this->users->first()->name)
            ->assertDontSee($this->users->last()->name);
    }
}
