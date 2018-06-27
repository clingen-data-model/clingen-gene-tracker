<?php

namespace Tests\Unit\Policies;

use App\ExpertPanel;
use App\Policies\CurationPolicy;
use App\Curation;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CurationPolicyTest extends TestCase
{
    use DatabaseTransactions;

    public function setUp()
    {
        parent::setUp();
        $this->policy = new CurationPolicy();
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
    public function user_not_curation_curator_cant_update_curation()
    {
        $user = factory(User::class)->create();
        $curation = factory(Curation::class)->create(['curator_id' => $this->curator->id]);
        $this->assertFalse($this->policy->update($user, $curation));
    }

    /**
     * @test
     */
    public function curation_curator_can_update_curation()
    {
        $curation = factory(Curation::class)->create(['curator_id'=>$this->curator->id]);
        $this->assertTrue($this->policy->update($this->curator, $curation));
    }

    /**
     * @test
     */
    public function coordinator_of_curation_expert_panel_can_update_curation()
    {
        $curation = factory(Curation::class)->create(['curator_id' => $this->curator->id, 'expert_panel_id'=>$this->panel->id]);
        $this->assertTrue($this->policy->update($this->coordinator, $curation));
    }

    /**
     * @test
     */
    public function user_with_manage_panel_curations_permission_can_update_curation()
    {
        $otherUser = factory(User::class)->create();
        $otherUser->expertPanels()->attach([$this->panel->id => ['can_edit_curations' => 1]]);

        $curation = factory(Curation::class)->create(['curator_id' => $this->curator->id, 'expert_panel_id' => $this->panel->id]);
        $this->assertTrue($this->policy->update($otherUser, $curation));
    }
}
