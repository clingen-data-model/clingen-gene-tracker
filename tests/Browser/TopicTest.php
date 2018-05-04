<?php

namespace Tests\Browser;

use App\Topic;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class TopicTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function setUp()
    {
        parent::setUp();
        $this->user = factory(\App\User::class)->create();
        $this->panels = factory(\App\ExpertPanel::class, 3)->create();
        $this->statuses = factory(\App\TopicStatus::class, 4)->create();
    }

    /**
     * @test
     */
    public function index_lists_all_topics()
    {
        $topics = factory(\App\Topic::class, 22)->create();

        $this->browse(function (Browser $browser) use ($topics) {
            $sequence = $browser->loginAs($this->user)
                ->visit('/')
                ->waitFor('.topics-table')
                ->pause(1000)
                ->waitFor('.topics-table-pagination')
                ;
            foreach ($topics as $idx => $topic) {
                $sequence->assertSee($topic->gene_symbol);
                if (($idx+1)%10 == 0) {
                    $sequence->click(".topics-table-pagination .page-item:last-child .page-link");
                }
            }
        });
    }

    /**
     * @test
     */
    public function a_user_can_edit_a_topic()
    {
        $panel = factory(\App\ExpertPanel::class)->create();
        $topic = factory(\App\Topic::class)->create(['expert_panel_id' => 1]);
        $this->browse(function (Browser $browser) use ($topic) {
            $browser->loginAs($this->user)
                ->visit('/')
                ->waitFor('#edit-topic-'.$topic->id.'-btn')
                ->click('#edit-topic-'.$topic->id.'-btn')
                ->waitFor('#edit-topic-modal')
                ->assertSeeIn('#edit-topic-modal', 'Edit Topic: '.$topic->gene_symbol.' for '.$topic->expertPanel->name);
        });
    }

    /**
     * @test
     */
    public function a_user_can_create_a_topic_record()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                    ->visit('/')
                    ->assertSee('ClinGen')
                    ->waitFor('#new-topic-btn')
                    ->click('#new-topic-btn')
                    ->waitFor('#new-topic-form')
                    ->type('#gene-symbol-input', 'TEST-1')
                    ->waitFor('#expert-panel-select option[value="1"]')
                    ->select('#expert-panel-select', 1)
                    ->click('#create-and-continue-btn')
                    ->waitFor('#topics-container')
                    ->pause(1000)
                    ->assertSee('TEST-1')
                    ->assertSee($this->panels->first()->name);
        });
    }
}
