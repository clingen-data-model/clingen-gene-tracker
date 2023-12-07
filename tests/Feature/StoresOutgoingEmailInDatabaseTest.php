<?php

namespace Tests\Feature;

use App\Email;
use Tests\TestCase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        Mail::raw('plain text message', function ($message) {
            $message->from('john@johndoe.com', 'John Doe');
            $message->sender('john@johndoe.com', 'John Doe');
            $message->to('john@johndoe.com', 'John Doe');
            $message->cc('john@johndoe.com', 'John Doe');
            $message->bcc('john@johndoe.com', 'John Doe');
            $message->replyTo('john@johndoe.com', 'John Doe');
            $message->subject('Subject');
        });

        $email = Email::orderBy('id', 'desc')->first();
        $this->assertEquals(['john@johndoe.com' => 'John Doe'], $email->from);
        $this->assertEquals(['john@johndoe.com' => 'John Doe'], $email->sender);
        $this->assertEquals(['john@johndoe.com' => 'John Doe'], $email->to);
        $this->assertEquals(['john@johndoe.com' => 'John Doe'], $email->cc);
        $this->assertEquals(['john@johndoe.com' => 'John Doe'], $email->bcc);
        $this->assertEquals(['john@johndoe.com' => 'John Doe'], $email->reply_to);
        $this->assertEquals('Subject', $email->subject);
        $this->assertEquals('plain text message', $email->body);
    }
}
