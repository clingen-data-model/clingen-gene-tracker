<?php

namespace Tests\Unit\Notifications;

use Tests\TestCase;
use App\Notifications\DigestRecipients;
use Illuminate\Support\Facades\Log;

class DigestRecipientsTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\Test]
    public function normalizes_and_deduplicates_addresses()
    {
        $this->assertEquals(
            ['a@example.com', 'b@example.com'],
            DigestRecipients::normalize(' A@Example.com, b@example.com ,,a@example.com')
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function drops_and_logs_invalid_addresses()
    {
        Log::spy();

        $this->assertEquals(
            ['a@example.com'],
            DigestRecipients::normalize('not-an-email, a@example.com')
        );

        Log::shouldHaveReceived('error')
            ->once()
            ->withArgs(fn ($message, $context) => $context === ['email' => 'not-an-email']);
    }
}
