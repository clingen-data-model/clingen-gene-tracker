<?php

namespace Tests\Feature\Notifications\Disease;

use Tests\TestCase;
use Tests\Traits\SetsUpDiseaseWithCuration;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Notifications\DigestibleNotificationInterface;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Notifications\Disease\MondoTermObsoleteNotification;

/**
 * @group mondo
 * @group mondo-notifications
 */
class MondoTermObsoletedNotificationTest extends TestCase
{
    use DatabaseTransactions;
    use SetsUpDiseaseWithCuration;

    public function setup():void
    {
        parent::setup();
        $this->setupDiseaseWithCuration(['name' => 'bob', 'is_obsolete' => false]);
    }

    /**
     * @test
     */
    public function sends_notifiation_if_disease_made_obsolete()
    {
        Notification::fake();
        $this->disease->update(['is_obsolete' => true]);
        Notification::assertSentTo(
            $this->user1, 
            MondoTermObsoleteNotification::class,
            function ($notification) {
                return $this->curation->id == $notification->curation->id
                    && $notification->via($this->user1) == ['mail']
                    && !($notification instanceof DigestibleNotificationInterface);
            }
        );
    }
    
    
}
