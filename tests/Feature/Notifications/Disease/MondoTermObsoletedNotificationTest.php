<?php

namespace Tests\Feature\Notifications\Disease;

use Tests\TestCase;
use Illuminate\Support\Facades\View;
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

    private $curation, $user1, $disease;

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

    /**
     * @test
     */
    public function renders_obsoletion_mail_template()
    {
        $view = View::make('email.curations.mondo_term_obsoleted', ['curation' => $this->curation, 'notifiable' => $this->user1]);
        $html = $view->render();

        $this->assertStringContainsString($this->curation->expertPanel->name, $html);
        $this->assertStringContainsString($this->curation->disease->name, $html);
    }
    
    
    
}
