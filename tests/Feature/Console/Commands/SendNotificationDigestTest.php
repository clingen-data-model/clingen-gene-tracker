<?php

namespace Tests\Feature\Console\Commands;

use App\User;
use App\Curation;
use App\Phenotype;
use Carbon\Carbon;
use Tests\TestCase;
use Database\Seeders\RolesAndPermissionsSeeder;
use Spatie\Permission\Models\Role;
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

    private const ALL_DIGESTS = 'receive all curation digests';

    public function setup():void
    {
        parent::setup();
        $this->seed(RolesAndPermissionsSeeder::class);
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
    public function sends_combined_digest_to_permission_holders_in_place_of_their_own()
    {
        $extra = $this->digestRecipient();
        $this->user1->givePermissionTo(self::ALL_DIGESTS);
        Notification::fake();

        $this->artisan('send-notifications');

        foreach ([$this->user1, $extra] as $recipient) {
            Notification::assertSentTo($recipient, CurationNotificationsDigest::class, function ($notification) {
                return $notification->groupedNotifications->count() == 5;
            });
        }
        Notification::assertSentToTimes($this->user1, CurationNotificationsDigest::class, 1);
        Notification::assertSentTo($this->user2, CurationNotificationsDigest::class, function ($notification) {
            return $notification->groupedNotifications->count() == 1;
        });
        $this->assertEquals(0, $this->user1->fresh()->unreadNotifications->count());
        $this->assertEquals(0, $this->user2->fresh()->unreadNotifications->count());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function sends_combined_digest_to_holders_of_a_role_with_the_permission()
    {
        $role = Role::create(['name' => 'digest-test-role']);
        $role->givePermissionTo(self::ALL_DIGESTS);
        $extra = factory(User::class)->create();
        $extra->assignRole($role);
        Notification::fake();

        $this->artisan('send-notifications');

        Notification::assertSentTo($extra, CurationNotificationsDigest::class, function ($notification) {
            return $notification->groupedNotifications->count() == 5;
        });
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function skips_deactivated_permission_holders()
    {
        $extra = $this->digestRecipient();
        $extra->update(['deactivated_at' => Carbon::now()]);
        Notification::fake();

        $this->artisan('send-notifications');

        Notification::assertNotSentTo($extra, CurationNotificationsDigest::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function leaves_everything_unread_when_combined_digest_fails_for_every_recipient()
    {
        $extra = $this->digestRecipient();
        $this->failMailTo([$extra->email]);

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
        $this->digestRecipient();
        $this->user1->givePermissionTo(self::ALL_DIGESTS);
        $this->failMailTo([$this->user1->email]);

        Artisan::call('send-notifications');

        $this->assertEquals(5, $this->user1->fresh()->unreadNotifications->count());
        $this->assertEquals(0, $this->user2->fresh()->unreadNotifications->count());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function continues_to_other_users_when_one_users_digest_fails()
    {
        $this->failMailTo([$this->user1->email]);

        Artisan::call('send-notifications');

        $this->assertEquals(5, $this->user1->fresh()->unreadNotifications->count());
        $this->assertEquals(0, $this->user2->fresh()->unreadNotifications->count());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function fails_the_run_when_every_users_digest_fails()
    {
        $this->failMailTo([$this->user1->email, $this->user2->email]);

        try {
            Artisan::call('send-notifications');
            $this->fail('Expected the digest failure to propagate.');
        } catch (\RuntimeException $exception) {
            $this->assertEquals('mail failed', $exception->getMessage());
        }

        $this->assertEquals(5, $this->user1->fresh()->unreadNotifications->count());
        $this->assertEquals(1, $this->user2->fresh()->unreadNotifications->count());
    }

    private function digestRecipient(): User
    {
        return tap(factory(User::class)->create())->givePermissionTo(self::ALL_DIGESTS);
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
