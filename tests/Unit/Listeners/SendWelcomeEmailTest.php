<?php

namespace Tests\Unit\Listeners;

use Tests\TestCase;
use App\Events\User\Created;
use App\Listeners\SendWelcomeEmail;
use App\Notifications\users\Welcome;
use Illuminate\Support\Facades\Notification;
use Illuminate\Foundation\Testing\DatabaseTransactions;

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
        Notification::fake();
        \Event::fake();
        $u = factory(\App\User::class)->create();
        $listener = new SendWelcomeEmail();
        $event = new Created($u);
        $listener->handle($event);

        Notification::assertSentTo($u, Welcome::class);
    }
}
