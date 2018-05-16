<?php

namespace Tests\Unit\Models;

use App\Events\User\Created;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group users
 * @group models
 */
class UserTest extends TestCase
{
    use RefreshDatabase;

    public function setUp()
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
}
