<?php

namespace Tests\Unit\Models;

use App\Events\User\Created;
use App\User;
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
}
