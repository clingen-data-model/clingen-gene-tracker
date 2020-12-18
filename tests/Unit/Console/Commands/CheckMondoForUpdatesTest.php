<?php

namespace Tests\Unit\Console\Commands;

use App\Console\Commands\CheckMondoForUpdates;
use App\Curation;
use App\ExpertPanel;
use App\Notifications\Curations\MondoIdNotFound;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CheckMondoForUpdatesTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @test
     */
    public function it_can_send_notifications_for_a_collection_of_curations()
    {
        $ep = factory(ExpertPanel::class)->create();

        $user = factory(User::class)->create();
        $user->expertPanels()->attach([$ep->id => ['is_coordinator' => 1]]);
        $this->assertTrue($user->isCoordinator());
        $curations = factory(Curation::class, 2)->create(['mondo_id' => 'MONDO:0007777', 'expert_panel_id' => $ep->id]);

        $consoleCommand = new CheckMondoForUpdates();

        Notification::fake();
        $consoleCommand->sendNotificationForCurations($curations);

        Notification::assertSentTo([$user], MondoIdNotFound::class);
    }
}
