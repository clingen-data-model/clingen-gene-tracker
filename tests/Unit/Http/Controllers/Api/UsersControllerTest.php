<?php

namespace Tests\Unit\Http\Controllers\Api;

use Tests\TestCase;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;

/**
 * @group api
 * @group users
 */
class UsersControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function setUp(): void
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
        $this->withoutExceptionHandling();
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
            ->assertJsonFragment(['name' => $this->users->first()->name])
            ->assertJsonFragment(['name' => $this->users->last()->name]);
    }

    /**
     * @test
     */
    public function filters_users_by_role()
    {
        $curators = factory(\App\User::class, 2)->create()
                        ->each(function ($user) {
                            $user->assignRole('admin');
                        });

        $this->actingAs($this->user, 'api')
            ->call('GET', 'api/users?role=admin')
            ->assertJsonFragment(['name' => $curators->first()->name])
            ->assertJsonFragment(['name' => $curators->last()->name])
            ->assertJsonMissing(['name' => $this->users->first()->name])
            ->assertJsonMissing(['name' => $this->users->last()->name]);
    }

    /**
     * @test
     */
    public function index_can_return_users_with_role()
    {
        $this->withoutExceptionHandling();
        $curators = factory(\App\User::class, 2)->create()
                        ->each(function ($user) {
                            $user->assignRole('admin');
                        });

        $this->actingAs($this->user, 'api')
            ->call('GET', 'api/users?with=roles')
            ->assertJsonFragment(['name' => 'admin']);
    }

    /**
     * @test
     */
    public function index_can_return_users_with_expert_panels()
    {
        $this->withoutExceptionHandling();
        $curators = factory(\App\User::class, 2)->create();

        $this->actingAs($this->user, 'api')
            ->call('GET', 'api/users?with=roles,expertPanels')
            ->assertSee('expert_panels');
    }
}
