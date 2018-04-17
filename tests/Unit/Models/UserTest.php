<?php

namespace Tests\Unit\Models;

use App\Events\User\Created;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group users
 */
class UserTest extends TestCase
{
    use RefreshDatabase;
    /**
     * @test
     */
    public function fires_UserCreated_event_when_created()
    {
        \Event::fake();
        $user = factory(\App\User::class)->create();
        \Event::assertDispatched(Created::class, function ($e) use ($user) {
            return $e->user->id = $user->id;
        });
    }
}
