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
                    ->getHandlers()[0]
                    ->getRecords();

        $this->assertCount(2, $records);
        $this->assertEquals('INFO', $records[1]['level_name']);
        $this->assertEquals(
            [
                'to' => ['test@example.org' => null],
                'from' => [config('mail.from.address') => config('mail.from.name')],
                'subject' => 'message subject',
                'body' => 'beans'
            ],
            $records[1]['context']
        );
    }
    
}
