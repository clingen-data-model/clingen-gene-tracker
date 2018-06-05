<?php

namespace Tests\Unit\Policies;

use App\ExpertPanel;
use App\Policies\TopicPolicy;
use App\Topic;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TopicPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function setUp()
    {
        parent::setUp();
        $this->policy = new TopicPolicy();
        $this->panel = factory(ExpertPanel::class)->create(['name'=>uniqid('panel')]);

        $this->curator = factory(User::class)->create();
        $this->coordinator = factory(User::class)->create();

        $this->panel->users()->sync([
            $this->curator->id => ['is_curator'=>true],
            $this->coordinator->id => ['is_coordinator'=>true]
        ]);
    }

    /**
     * @test
     */
    public function user_not_topic_curator_cant_update_topic()
    {
        $user = factory(User::class)->create();
        $topic = factory(Topic::class)->create(['curator_id' => $this->curator->id]);
        $this->assertFalse($this->policy->update($user, $topic));
    }

    /**
     * @test
     */
    public function topic_curator_can_update_topic()
    {
        $topic = factory(Topic::class)->create(['curator_id'=>$this->curator->id]);
        $this->assertTrue($this->policy->update($this->curator, $topic));
    }

    /**
     * @test
     */
    public function coordinator_of_topic_expert_panel_can_update_topic()
    {
        $topic = factory(Topic::class)->create(['curator_id' => $this->curator->id, 'expert_panel_id'=>$this->panel->id]);
        $this->assertTrue($this->policy->update($this->coordinator, $topic));
    }

    /**
     * @test
     */
    public function user_with_manage_panel_topics_permission_can_update_topic()
    {
        $otherUser = factory(User::class)->create();
        $otherUser->expertPanels()->attach([$this->panel->id => ['can_edit_topics' => 1]]);

        $topic = factory(Topic::class)->create(['curator_id' => $this->curator->id, 'expert_panel_id' => $this->panel->id]);
        $this->assertTrue($this->policy->update($otherUser, $topic));
    }
}
