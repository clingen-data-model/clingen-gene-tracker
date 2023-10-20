<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * @group roles
 */
class NavCustomizationTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);
        $this->pr = factory(\App\User::class)->create();
        $this->pr->assignRole('programmer');

        $this->ad = factory(\App\User::class)->create();
        $this->ad->assignRole('admin');

        $this->cu = factory(\App\User::class)->create();
    }

    /**
     * @test
     *
     * @group nav
     */
    public function programmers_can_see_admin_link(): void
    {
        $this->actingAs($this->pr)
            ->call('GET', '/')
            ->assertSee('Admin');
    }

    /**
     * @test
     *
     * @group nav
     */
    public function admins_can_see_admin_link(): void
    {
        $this->actingAs($this->ad)
            ->call('GET', '/')
            ->assertSee('Admin');
    }

    /**
     * @test
     *
     * @group nav
     */
    public function others_cannot_see_admin_link(): void
    {
        $this->actingAs($this->cu)
            ->call('GET', '/')
            ->assertDontSee('Admin');
    }

    /**
     * @test
     *
     * @group nav
     */
    public function guests_cannot_see_admin_link(): void
    {
        $this->call('GET', '/')
            ->assertDontSee('Admin');
    }
}
