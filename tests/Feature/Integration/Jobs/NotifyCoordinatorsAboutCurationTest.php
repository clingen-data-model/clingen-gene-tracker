<?php

namespace Tests\Feature\Integration\Jobs;

use App\Curation;
use App\Jobs\NotifyCoordinatorsAboutCuration;
use App\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Notification;
use Tests\Dummies\CurationNotification;
use Tests\TestCase;

class NotifyCoordinatorsAboutCurationTest extends TestCase
{
    use DatabaseTransactions;

    public function setup(): void
    {
        parent::setup();
        $this->curation = factory(Curation::class)->create(['gene_symbol' => 'BRCA1', 'curation_status_id' => 1]);
        $this->coordinator = factory(User::class)->create();
        $this->curation->expertPanel->addCoordinator($this->coordinator);
    }

    /**
     * @test
     */
    public function only_notifies_active_coordinators(): void
    {
        $inactiveCoord = factory(User::class)->create(['deactivated_at' => Carbon::now()]);
        $this->curation->expertPanel->addCoordinator($inactiveCoord);

        Notification::fake();

        Bus::dispatch(new NotifyCoordinatorsAboutCuration($this->curation, CurationNotification::class));

        Notification::assertSentto($this->coordinator, CurationNotification::class);
        Notification::assertNotSentTo($inactiveCoord, CurationNotification::class);
    }
}
