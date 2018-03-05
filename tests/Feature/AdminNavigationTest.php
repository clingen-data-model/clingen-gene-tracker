<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

/**
 * @group admin
 */
class AdminNavigationTest extends TestCase
{
    use DatabaseMigrations;

    public function setUp()
    {
        parent::setUp();
        // \Artisan::call('db:seed');
        $seeder = new \RolesAndPermissionsSeeder();
        $seeder->run();
        $this->u = factory(\App\User::class)->create();
    }

    /**
     * @test
     */
    public function programmers_can_see_dashboard()
    {
        $this->u->assignRole('programmer');

        $this->actingAs($this->u)
            ->call('GET', '/admin/dashboard')
            ->assertSee('Dashboard');
    }

    /**
     * @test
     */
    public function admins_can_see_dashboard()
    {
        $this->u->assignRole('admin');

        $this->actingAs($this->u)
            ->call('GET', '/admin/dashboard')
            ->assertSee('Dashboard');
    }

    /**
     * @test
     */
    public function curators_can_not_see_dashboard()
    {
        $this->u->assignRole('curator');

        $this->actingAs($this->u)
            ->call('GET', '/admin/dashboard')
            ->assertStatus(403);
    }

    /**
     * @test
     */
    public function coordinators_can_not_see_dashboard()
    {
        $this->u->assignRole('coordinator');

        $this->actingAs($this->u)
            ->call('GET', '/admin/dashboard')
            ->assertStatus(403);
    }

    /**
     * @test
     */
    public function programmers_can_see_users()
    {
        $this->u->assignRole('programmer');

        $this->actingAs($this->u)
            ->call('GET', '/admin/dashboard')
            ->assertSee('Users');
    }

    /**
     * @test
     */
    public function admins_can_see_users()
    {
        $this->u->assignRole('admin');

        $this->actingAs($this->u)
            ->call('GET', '/admin/dashboard')
            ->assertSee('Users');
    }

    /**
     * @test
     */
    public function curators_can_not_see_users()
    {
        $this->u->assignRole('curator');

        $this->actingAs($this->u)
            ->call('GET', '/admin/dashboard')
            ->assertDontSee('Users');
    }

    /**
     * @test
     */
    public function coordinators_can_not_see_users()
    {
        $this->u->assignRole('coordinator');

        $this->actingAs($this->u)
            ->call('GET', '/admin/dashboard')
            ->assertDontSee('Users');
    }
}
