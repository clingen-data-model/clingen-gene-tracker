<?php

namespace Tests\Feature;

use App\Curation;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Notification;
use App\Notifications\Curations\MondoIdNotFound;
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

    /**
     * @test
     * @group mail
     * @group notifications
     * @group logging
     */
    public function system_logs_notification_sent_as_mail()
    {
        // \Mail::raw('beans', function ($message) {
        //     $message->subject('message subject')->to('test@example.org');
        // });

        Notification::route('mail', 'test@example.org')
            ->notify(new MondoIdNotFound(factory(Curation::class)->create()));

        $records = app('log')
                    ->channel('mail')
                    ->getHandlers()[0]
                    ->getRecords();

        $this->assertCount(1, $records);
        $this->assertEquals('INFO', $records[0]['level_name']);
        $this->assertContains('To: test@example.org', $records[0]['message']);
        $this->assertContains('Subject: Mondo id not found', $records[0]['message']);
        $this->assertContains('The MonDO ID is not correctly formatted', $records[0]['message']);
    }
}
