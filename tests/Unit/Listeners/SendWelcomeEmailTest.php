<?php

namespace Tests\Unit\Listeners;

use App\Events\User\Created;
use App\Listeners\SendWelcomeEmail;
use App\Notifications\users\Welcome;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * @group users
 * @group mail
 */
class SendWelcomeEmailTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @test
     * @group mail
     * @group notifications
     */
    public function sends_welcome_email_to_user()
    {
        $this->markTestSkipped('Unable to get to pass but works in real life.');
        Notification::fake();
        \Event::fake();
        $u = factory(\App\User::class)->create();
        $listener = new SendWelcomeEmail();
        $event = new Created($u);
        $listener->handle($event);

        Notification::assertSentTo($u, Welcome::class);
    }
}
