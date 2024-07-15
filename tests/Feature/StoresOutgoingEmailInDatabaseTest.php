<?php

namespace Tests\Feature;

use App\User;
use App\Email;
use Tests\TestCase;
use App\Notifications\Users\Welcome;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class StoresOutgoingEmailInDatabaseTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @test
     * @group mail
     */
    public function stores_outgoing_mail_in_database()
    {
        $user = factory(User::class)->create();
        Notification::send($user, new Welcome());

        $email = Email::orderBy('id', 'desc')->first();
        $this->assertEquals([config('mail.from.address') => config('mail.from.name')], $email->from);
        $this->assertEquals(null, $email->sender);
        $this->assertEquals([$user->email => ''], $email->to);
        $this->assertEquals('Welcome to ClinGen GeneTracker', $email->subject);
        $this->assertMatchesRegularExpression('/Welcome to the  system./', $email->body);
    }
}
