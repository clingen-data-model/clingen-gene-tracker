<?php

namespace Tests\Feature\End2End\Dx;

use App\User;
use stdClass;
use App\Curation;
use App\DataExchange\Actions\ConsumeMondoNotifications;
use Tests\TestCase;
use Illuminate\Support\Facades\View;
use App\Notification as AppNotification;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Notification;
use App\Jobs\NotifyCoordinatorsAboutCuration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\DataExchange\Actions\NotifyMondoObsoletionCandidate;
use App\DataExchange\Contracts\MessageConsumer;
use App\DataExchange\Notifications\MondoObsoletionCandidateNotification;
use App\DataExchange\Testing\TestConsumer;

class MondoObsoletionCandidateTest extends TestCase
{
    use DatabaseTransactions;
    
    const MONDO_ID = "MONDO:0007139";

    public function setup():void
    {
        parent::setup();
        $this->curation = $this->setupCuration(['mondo_id' => self::MONDO_ID]);
        $this->user = $this->setupUser();
        $this->curation->expertPanel->addCoordinator($this->user);
        $this->obMsg = (object)[
            "release_name" =>"v2022-02-04",
            "release_tag" =>"1.4",
            "release_date" =>"2022-02-22T16:25:04Z",
            "release_author" =>"larrybabb",
            "event_type" =>"obsoletion_candidate",
            "content" => (object)[
                "mondo_id" => self::MONDO_ID,
                "label" =>"Antipyrine metabolism",
                "comment" =>"Reason: out of scope. This is a trait or legacy term from OMIM and not suitabale for Mondo Term to consider: none",
                "issue" =>"https://github.com/monarch-initiative/mondo/issues/3637",
                "obsoletion_date" =>"2022-04-01",
            ]
        ];
    }
    
    /**
     * @test
     */
    public function consumes_mondo_topic_and_dispatches_notifications()
    {
        app()->bind(MessageConsumer::class, function () {
            return new TestConsumer([
                (object)['payload' => json_encode($this->obMsg)],
                (object)['payload' => json_encode([
                    "release_name" =>"v2022-02-04",
                    "release_tag" =>"1.4",
                    "release_date" =>"2022-02-22T16:25:04Z",
                    "release_author" =>"larrybabb",
                    "event_type" =>"obsoleted", // This is the difference
                    "content" => (object)[
                        "mondo_id" => self::MONDO_ID,
                        "label" =>"Antipyrine metabolism",
                        "comment" =>"Reason: out of scope. This is a trait or legacy term from OMIM and not suitabale for Mondo Term to consider: none",
                        "issue" =>"https://github.com/monarch-initiative/mondo/issues/3637",
                        "obsoletion_date" =>"2022-04-01",
                    ]
                ])]
            ]);
        });

        $consumeAction = app()->make(ConsumeMondoNotifications::class);

        Notification::fake();
        $consumeAction->handle();

        Notification::assertSentToTimes(
            $this->user, 
            MondoObsoletionCandidateNotification::class, 
            1
        );
    }
    
    
    /**
     * @test
     */
    public function notifies_coordinators_of_curations_with_mondo_id()
    {
        Notification::fake();
        $this->runAction();
        Notification::assertSentTo($this->user, MondoObsoletionCandidateNotification::class, function ($notification) {
            return $notification->curation->id == $this->curation->id
                 && $notification->messageData == $this->obMsg;
        });
    }

    /**
     * @test
     */
    public function digest_notification_renders_correctly()
    {
        // Send the notification via the action.
        $this->runAction();
        // Get all notifications
        $notifications = AppNotification::orderBy('created_at')->limit(1)->get();
        
        // Make the view.
        $view = View::make($notifications->first()->data['template'], compact('notifications'));
        $html = $view->render();

        $this->assertStringContainsString($this->obMsg->content->obsoletion_date, $html);
        $this->assertStringContainsString($this->obMsg->content->mondo_id, $html);
        $this->assertStringContainsString($this->obMsg->content->label, $html);
        $this->assertStringContainsString($this->obMsg->content->comment, $html);
        $this->assertStringContainsString($this->obMsg->content->issue, $html);
        $this->assertStringContainsString($this->curation->expertPanel->name, $html);
        $this->assertStringContainsString($this->curation->gene_symbol, $html);
    }
    
    private function runAction($msg = null)
    {
        $msg = $msg ?? $this->obMsg;
        (new NotifyMondoObsoletionCandidate())->handle($msg);
    }
    
    
}
