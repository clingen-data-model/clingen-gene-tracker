<?php

namespace Tests\Feature;

use Tests\TestCase;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;

/**
 * @group admin
 */
class AdminNavigationTest extends TestCase
{
    use DatabaseTransactions;

    public function setUp(): void
    {
        parent::setUp();
        $seeder = new RolesAndPermissionsSeeder();
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
    public function others_can_not_see_dashboard()
    {
        $this->markTestSkipped();
        $this->actingAs($this->u)
            ->call('GET', '/admin/dashboard')
            ->assertRedirect('/admin/login');
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
    public function others_can_not_see_users()
    {
        $this->actingAs($this->u)
            ->call('GET', '/admin/dashboard')
            ->assertDontSee('Users');
    }
}
