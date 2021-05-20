<?php

namespace Tests\Feature\Console\Commands;

use App\Affiliation;
use App\ExpertPanel;
use App\DataExchange\Notifications\StreamErrorNotification;
use App\StreamError;
use App\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * @group notifications
 * @group gci
 */
class CreateNotificationsForStreamErrorsTest extends TestCase
{
    use DatabaseTransactions;

    public function setup(): void
    {
        parent::setup();
        $this->streamError1 = factory(StreamError::class)->create([
            'message_payload' => json_decode(file_get_contents(base_path().'/tests/files/gci_messages/approved_with_status_date.json')),
        ]);

        $this->admin = factory(User::class)->create();
        $this->admin->assignRole('admin');

        $this->coordinator = factory(User::class)->create();
        [$this->ep1, $this->ep2] = factory(ExpertPanel::class, 2)->create();
        $this->coordinator->expertPanels()->attach($this->ep1, ['is_coordinator' => true]);
    }

    /**
     * @test
     */
    public function creates_notification_for_admin_if_no_expert_panel_for_affiliation()
    {
        Notification::fake();
        $this->artisan('streaming-service:notify-errors');

        Notification::assertSentTo($this->admin, StreamErrorNotification::class);
    }

    /**
     * @test
     */
    public function creates_notification_for_admin_if_no_coordinators_for_panel_related_to_affiliation()
    {
        Notification::fake();
        $this->ep2->update(['affiliation_id' => $this->streamError1->affilaition_id]);

        $this->artisan('streaming-service:notify-errors');

        Notification::assertSentTo($this->admin, StreamErrorNotification::class);
    }

    /**
     * @test
     */
    public function creates_notification_for_coordinator_if_affliation_related_to_coordinators_ep()
    {
        Notification::fake();
        $affiliation = Affiliation::findByClingenId($this->streamError1->affiliation_id);
        $this->ep1->update(['affiliation_id' => $affiliation->id]);
        $affiliation = Affiliation::findByClingenId($this->streamError1->affiliation_id);

        $this->artisan('streaming-service:notify-errors');

        Notification::assertNotSentTo($this->admin, StreamErrorNotification::class);
        Notification::assertSentTo($this->coordinator, StreamErrorNotification::class);
    }

    /**
     * @test
     */
    public function marks_pending_stream_errors_sent()
    {
        Carbon::setTestNow('2020-06-01 00:00:00');
        $this->artisan('streaming-service:notify-errors');
        $this->assertDatabaseHas('stream_errors', [
            'id' => $this->streamError1->id,
            'notification_sent_at' => '2020-06-01 00:00:00',
        ]);
    }
}
