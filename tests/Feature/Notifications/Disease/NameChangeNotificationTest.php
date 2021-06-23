<?php

namespace Tests\Feature\Notifications\Disease;

use App\User;
use App\Disease;
use App\Curation;
use Tests\TestCase;
use App\Events\Disease\DiseaseNameChanged;
use App\Notifications\DigestibleNotificationInterface;
use Tests\Traits\SetsUpDiseaseWithCuration;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Notifications\Disease\NameChangedNotification;
use Illuminate\Foundation\Testing\DatabaseTransactions;

/**
 * @group mondo
 * @group mondo-notifications
 */
class NameChangeNotificationTest extends TestCase
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
    public function notification_sent_to_coordinators_of_curations_with_disease_if_not_obsolete()
    {
        Notification::fake();
        $this->disease->update(['name' => 'New Name!!']);
        Notification::assertSentTo($this->user1, NameChangedNotification::class, function ($notification) {
            return $notification->curation->id == $this->curation->id 
                && $notification->oldName == 'bob'
                && $notification->via($this->user1) == ['database']
                && $notification instanceof DigestibleNotificationInterface;
        });
    }

    /**
     * @test
     */
    public function notification_not_sent_if_desease_is_obsolete()
    {
        Notification::fake();
        $this->disease->update(['name' => 'New Name!!', 'is_obsolete' => true]);
        Notification::assertNotSentTo($this->user1, NameChangedNotification::class);
    }
    
    
    
}
