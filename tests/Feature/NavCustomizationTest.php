<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group roles
 */
class NavCustomizationTest extends TestCase
{
    use RefreshDatabase;

    public function setUp()
    {
        parent::setUp();
        \Artisan::call('db:seed');
        $this->pr = factory(\App\User::class)->create();
        $this->pr->assignRole('programmer');

        $this->ad = factory(\App\User::class)->create();
        $this->ad->assignRole('admin');

        $this->cu = factory(\App\User::class)->create();
        $this->cu->assignRole('curator');

        $this->co = factory(\App\User::class)->create();
        $this->co->assignRole('coordinator');

    }

    /**
     * @test
     * @group nav
     */
    public function programmers_can_see_admin_link()
    {
        $this->actingAs($this->pr)
            ->call('GET', '/')
            ->assertSee('Admin');
    }

    /**
     * @test
     * @group nav
     */
    public function admins_can_see_admin_link()
    {
        $this->actingAs($this->ad)
            ->call('GET', '/')
            ->assertSee('Admin');
    }

    /**
     * @test
     * @group nav
     */
    public function coordinators_cannot_see_admin_link()
    {
        $this->actingAs($this->co)
            ->call('GET', '/')
            ->assertDontSee('Admin');
    }

    /**
     * @test
     * @group nav
     */
    public function curators_cannot_see_admin_link()
    {
        $this->actingAs($this->cu)
            ->call('GET', '/')
            ->assertDontSee('Admin');
    }

    /**
     * @test
     * @group nav
     */
    public function guests_cannot_see_admin_link()
    {
        $this->call('GET', '/')
            ->assertDontSee('Admin');
    }
}
