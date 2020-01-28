<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LogMailSentMailTest extends TestCase
{
    /**
     * @test
     */
    public function system_logs_mail_when_sent()
    {
        \Mail::raw('beans', function ($message) {
            $message->subject('message subject')->to('test@example.org');
        });

        $records = app('log')
                    ->channel('mail')
                    ->getHandlers()[0]
                    ->getRecords();

        $this->assertCount(1, $records);
        $this->assertEquals('INFO', $records[0]['level_name']);
        $this->assertContains('To: test@example.org', $records[0]['message']);
        $this->assertContains('Subject: message subject', $records[0]['message']);
        $this->assertContains('beans', $records[0]['message']);
    }
}
