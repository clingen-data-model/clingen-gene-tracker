<?php

namespace Tests\Unit\Models;

use App\Events\User\Created;
use App\ExpertPanel;
use App\User;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * @group users
 * @group models
 */
class UserTest extends TestCase
{
    use DatabaseTransactions;

    public function setUp(): void
    {
        parent::setUp();
        $this->user = factory(\App\User::class)->create();
    }

    /**
     * @test
     */
    public function fires_UserCreated_event_when_created()
    {
        \Event::fake();
        $user = factory(\App\User::class)->create();
        \Event::assertDispatched(Created::class, function ($e) {
            return $e->user->id = $this->user->id;
        });
    }

    /**
     * @test
     */
    public function user_belongsToMany_exper_panels()
    {
        $this->assertInstanceOf(BelongsToMany::class, $this->user->expertPanels());
    }

    /**
     * @test
     */
    public function user_gets_random_password_on_creating_if_not_set()
    {
        $u = factory(User::class)->create([
            'password'=> null
        ]);
        $this->assertNotNull($u->password);
    }

    /**
     * @test
     */
    public function user_creating_with_password_gets_that_password()
    {
        $u = factory(User::class)->create([
            'password' => 'secret'
        ]);

        $this->assertTrue(\Hash::check('secret', $u->password));
    }

    /**
     * @test
     */
    public function user_password_hashed_on_assignment()
    {
        $u = factory(User::class)->create([
            'password' => 'test'
        ]);

        $this->assertNotEquals('test', $u->password);
        $this->assertTrue(\Hash::check('test', $u->password));
    }

    /**
     * @test
     */
    public function can_get_wether_user_can_edit_panel_curations()
    {
        $panel = factory(ExpertPanel::class)->create();
        $this->assertFalse($this->user->canEditPanelCurations($panel));

        $this->user->expertPanels()->attach($panel->id);
        $this->assertFalse($this->user->fresh()->canEditPanelCurations($panel));

        $this->user->expertPanels()->sync([$panel->id => ['can_edit_curations'=>1]]);
        $this->assertTrue($this->user->fresh()->canEditPanelCurations($panel));

        $this->user->expertPanels()->sync([$panel->id => ['can_edit_curations'=>0, 'is_coordinator'=>1]]);
        $this->assertTrue($this->user->fresh()->canEditPanelCurations($panel));
    }

    /**
     * @test
     */
    public function knows_if_user_is_panel_coordinator()
    {
        $panel = factory(ExpertPanel::class)->create();
        $this->assertFalse($this->user->isPanelCoordinator($panel));

        $this->user->expertPanels()->attach($panel->id);
        $this->assertFalse($this->user->fresh()->isPanelCoordinator($panel));

        $this->user->expertPanels()->sync([$panel->id => ['is_coordinator'=>1]]);
        $this->assertTrue($this->user->fresh()->isPanelCoordinator($panel));
    }

    /**
     * @test
     */
    public function knows_if_user_is_panel_curator()
    {
        $panel = factory(ExpertPanel::class)->create();
        $this->assertFalse($this->user->isPanelCurator($panel));

        $this->user->expertPanels()->attach($panel->id);
        $this->assertFalse($this->user->fresh()->isPanelCurator($panel));

        $this->user->expertPanels()->sync([$panel->id => ['is_curator'=>1]]);
        $this->assertTrue($this->user->fresh()->isPanelCurator($panel));
    }
}
