<?php

namespace Tests\Unit\Policies;

use App\ExpertPanel;
use App\Policies\TopicPolicy;
use App\Topic;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TopicPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function setUp()
    {
        parent::setUp();
        $this->policy = new TopicPolicy();
        $this->panel = factory(ExpertPanel::class)->create(['name'=>uniqid('panel')]);
        $this->managePanelTopicsPerm = Permission::create(['name' => 'manage panel topics']);
        $this->roleCurator = Role::create(['name' => 'curator']);
        $this->roleCoordinator = Role::create(['name' => 'coordinator']);
        $this->roleCoordinator->givePermissionTo('manage panel topics');

        $this->curator = factory(User::class)->create();
        $this->curator->assignRole('curator');
        $this->curator->expertPanels()->attach($this->panel->id);

        $this->coordinator = factory(User::class)->create();
        $this->coordinator->assignRole('coordinator');
        $this->coordinator->expertPanels()->attach($this->panel->id);
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
        $otherUser->expertPanels()->attach($this->panel->id);
        $otherUser->givePermissionTo('manage panel topics');

        $topic = factory(Topic::class)->create(['curator_id' => $this->curator->id, 'expert_panel_id' => $this->panel->id]);
        $this->assertTrue($this->policy->update($otherUser, $topic));
    }
}
