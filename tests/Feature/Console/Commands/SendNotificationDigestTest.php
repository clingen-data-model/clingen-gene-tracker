<?php

namespace Tests\Feature\Console\Commands;

use App\User;
use App\Curation;
use App\Phenotype;
use Carbon\Carbon;
use Tests\TestCase;
use Illuminate\Support\Facades\Notification;
use App\Notifications\Curations\MondoIdNotFound;
use App\Notifications\CurationNotificationsDigest;
use App\Notifications\Curations\GeneSymbolUpdated;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Notifications\Curations\PhenotypeOmimEntryMoved;
use App\Notifications\Curations\HgncIdNotFoundNotification;
use App\Notifications\Curations\PhenotypeNomenclatureUpdated;
use Illuminate\Notifications\AnonymousNotifiable;

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
    public function sends_combined_digest_to_additional_recipients()
    {
        config()->set('notifications.digest_additional_emails', [
            'recipient1@example.org',
            'recipient2@example.org',
        ]);

        Notification::fake();

        $this->artisan('send-notifications');

        // Existing users should still receive their own digests.
        Notification::assertSentTo(
            $this->user1,
            CurationNotificationsDigest::class
        );

        Notification::assertSentTo(
            $this->user2,
            CurationNotificationsDigest::class
        );

        // Check both additional recipients.
        foreach ([
            'recipient1@example.org',
            'recipient2@example.org',
        ] as $email) {
            Notification::assertSentOnDemand(
                CurationNotificationsDigest::class,
                function ($notification, $channels, $notifiable) use ($email) {
                    return $notifiable->routeNotificationFor('mail') === $email
                        && $notification->groupedNotifications->count() === 5;
                }
            );
        }
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function combined_digest_contains_all_individual_notifications()
    {
        config()->set('notifications.digest_additional_emails', [
            'recipient1@example.org',
        ]);

        Notification::fake();

        $this->artisan('send-notifications');

        $individualCount = 0;
        $combinedCount = null;

        foreach ([$this->user1, $this->user2] as $user) {
            Notification::assertSentTo(
                $user,
                CurationNotificationsDigest::class,
                function ($notification) use (&$individualCount) {
                    $individualCount += $notification
                        ->groupedNotifications
                        ->sum(fn ($group) => $group->count());

                    return true;
                }
            );
        }

        Notification::assertSentOnDemand(
            CurationNotificationsDigest::class,
            function ($notification, $channels, $notifiable) use (&$combinedCount) {
                if ($notifiable->routeNotificationFor('mail') !== 'recipient1@example.org') {
                    return false;
                }

                $combinedCount = $notification
                    ->groupedNotifications
                    ->sum(fn ($group) => $group->count());

                return true;
            }
        );

        $this->assertNotNull($combinedCount);
        $this->assertEquals($individualCount, $combinedCount);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function combined_digest_renders_for_email_only_recipient()
    {
        config()->set('notifications.digest_additional_emails', [
            'recipient1@example.org',
        ]);

        Notification::fake();

        $this->artisan('send-notifications');

        Notification::assertSentOnDemand(
            CurationNotificationsDigest::class,
            function ($notification, $channels, $notifiable) {
                if ($notifiable->routeNotificationFor('mail') !== 'recipient1@example.org') {
                    return false;
                }

                $mail = $notification->toMail($notifiable);

                $html = view('email.curation_notifications_digest', [
                    'groups' => $notification->groupedNotifications,
                    'user' => $notifiable,
                ])->render();

                $this->assertStringContainsString(
                    'Updates from the past week.',
                    $html
                );

                return true;
            }
        );
    }
}
