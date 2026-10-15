<?php

namespace Tests\Feature\Console\Commands;

use App\User;
use App\Curation;
use App\Phenotype;
use Carbon\Carbon;
use Tests\TestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Illuminate\Notifications\ChannelManager;
use App\Notifications\Curations\MondoIdNotFound;
use App\Notifications\CurationNotificationsDigest;
use App\Notifications\Curations\GeneSymbolUpdated;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Notifications\Curations\PhenotypeOmimEntryMoved;
use App\Notifications\Curations\HgncIdNotFoundNotification;
use App\Notifications\Curations\PhenotypeNomenclatureUpdated;

/**
 * @group notifications
 * @group mail
 */
#[\PHPUnit\Framework\Attributes\Group('notifications')]
#[\PHPUnit\Framework\Attributes\Group('mail')]

class SendNotificationDigestTest extends TestCase
{
    use DatabaseTransactions;

    public function setup():void
    {
        parent::setup();
        $users = factory(User::class, 2)->create();
        $this->user1 = $users->first();
        $this->user2 = $users->last();
        $curations = factory(Curation::class, 4)->create(['mondo_id' => '0afidafd83']);
        $phenotypes = factory(Phenotype::class, 2)->create([]);

        $realNow = Carbon::now();
        Carbon::setTestNow($realNow->subDays(8));
        $this->user1->notify(new GeneSymbolUpdated($curations->random(), 'ABCDE'));
        $this->user1->notifications->each->update(['read_at' => Carbon::now()]);
        
        Carbon::setTestNow($realNow->subDays(7));
        $this->user1->notify(new HgncIdNotFoundNotification($curations->random()));
        Carbon::setTestNow($realNow->subDays(6));
        $this->user1->notify(new MondoIdNotFound($curations->random()));
        $this->user2->notify(new PhenotypeNomenclatureUpdated($curations->random(), $phenotypes->random(), 'Bobsyeruncle'));
        Carbon::setTestNow($realNow);
        $this->user1->notify(new PhenotypeOmimEntryMoved($curations->random(), $phenotypes->take(2), 'beans', 123556));
        $this->user1->notify(new PhenotypeNomenclatureUpdated($curations->random(), $phenotypes->random(), 'Bobsyeruncle'));
        $this->user1->notify(new GeneSymbolUpdated($curations->random(), 'ABCDE'));
    }

    /**
     * @test
     */
#[\PHPUnit\Framework\Attributes\Test]

    public function sends_an_email_with_aggregated_notifications()
    {
        Notification::fake();
        $this->artisan('send-notifications');
        Notification::assertSentTo($this->user1, CurationNotificationsDigest::class, function ($notification) {
            return $notification->groupedNotifications->count() == 5;
        });
        
        Notification::assertSentTo($this->user2, CurationNotificationsDigest::class, function ($notification) {
            return $notification->groupedNotifications->count() == 1;
        });
    }

    /**
     * @test
     */
#[\PHPUnit\Framework\Attributes\Test]

    public function marks_notifications_read_when_sent()
    {
        $this->artisan('send-notifications');
        $this->assertEquals(0, $this->user1->unreadNotifications->count());
        $this->assertEquals(0, $this->user2->unreadNotifications->count());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function sends_combined_digest_to_additional_recipients_in_place_of_their_own()
    {
        config(['notifications.curation_digest_additional_emails' => $this->user1->email.', extra@example.com']);
        Notification::fake();

        $this->artisan('send-notifications');

        foreach ([$this->user1->email, 'extra@example.com'] as $email) {
            Notification::assertSentOnDemand(
                CurationNotificationsDigest::class,
                fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === $email
                    && $notification->groupedNotifications->count() == 5
            );
        }
        Notification::assertNotSentTo($this->user1, CurationNotificationsDigest::class);
        Notification::assertSentTo($this->user2, CurationNotificationsDigest::class);
        $this->assertEquals(0, $this->user1->fresh()->unreadNotifications->count());
        $this->assertEquals(0, $this->user2->fresh()->unreadNotifications->count());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function leaves_everything_unread_when_combined_digest_fails_for_every_recipient()
    {
        config(['notifications.curation_digest_additional_emails' => 'extra@example.com']);
        $this->failMailTo(['extra@example.com']);

        try {
            Artisan::call('send-notifications');
            $this->fail('Expected the combined digest failure to propagate.');
        } catch (\RuntimeException $exception) {
            $this->assertEquals('mail failed', $exception->getMessage());
        }

        $this->assertEquals(5, $this->user1->fresh()->unreadNotifications->count());
        $this->assertEquals(1, $this->user2->fresh()->unreadNotifications->count());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function continues_when_combined_digest_fails_for_some_recipients()
    {
        config(['notifications.curation_digest_additional_emails' => $this->user1->email.', extra@example.com']);
        $this->failMailTo([$this->user1->email]);

        Artisan::call('send-notifications');

        $this->assertEquals(5, $this->user1->fresh()->unreadNotifications->count());
        $this->assertEquals(0, $this->user2->fresh()->unreadNotifications->count());
    }

    private function failMailTo(array $failing): void
    {
        // The real mail driver may already be cached, which would shadow the extension.
        app(ChannelManager::class)->forgetDrivers()->extend('mail', fn () => new class($failing) {
            public function __construct(private array $failing)
            {
            }

            public function send($notifiable, $notification)
            {
                if (in_array($notifiable->routeNotificationFor('mail', $notification), $this->failing, true)) {
                    throw new \RuntimeException('mail failed');
                }
            }
        });
    }
}
