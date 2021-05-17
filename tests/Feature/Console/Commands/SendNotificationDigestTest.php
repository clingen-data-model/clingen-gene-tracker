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

/**
 * @group notifications
 * @group mail
 */
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
    public function marks_notifications_read_when_sent()
    {
        $this->artisan('send-notifications');
        $this->assertEquals(0, $this->user1->unreadNotifications->count());
        $this->assertEquals(0, $this->user2->unreadNotifications->count());
    }
}
