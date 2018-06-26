<?php

namespace Tests\Unit\models;

use App\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group models
 * @group expert-panels
 */
class ExpertPanelTest extends TestCase
{
    use RefreshDatabase;

    public function setUp()
    {
        parent::setUp();

        $this->panel = factory(\App\ExpertPanel::class)->create();
    }

    /**
     * @test
     */
    public function has_fillable_name()
    {
        $this->panel->update(['name'=>'test name']);
        $this->assertEquals('test name', $this->panel->name);
    }

    /**
     * @test
     */
    public function has_fillable_working_group_id()
    {
        $wg = factory(\App\WorkingGroup::class)->create();
        $this->panel->update(['working_group_id'=>$wg->id]);
        $this->assertEquals($wg->id, $this->panel->working_group_id);
    }

    /**
     * @test
     */
    public function belongsTo_WorkingGroup()
    {
        $this->assertInstanceOf(BelongsTo::class, $this->panel->workingGroup());
    }

    /**
     * @test
     */
    public function panel_hasMany_topics()
    {
        $this->panel->topics()->save(factory(\App\Topic::class)->create());

        $this->assertInstanceOf(HasMany::class, $this->panel->topics());
    }

    /**
     * @test
     */
    public function panel_belongsToMany_users()
    {
        $this->assertInstanceOf(BelongsToMany::class, $this->panel->users());
    }

    /**
     * @test
     */
    public function panel_belongsToMany_curators()
    {
        $u = factory(User::class)->create();
        $u2 = factory(User::class)->create();
        $this->panel->users()->sync([$u->id => ['is_curator' => true], $u2->id => ['is_curator' => false]]);
        $panel = $this->panel->fresh();

        $this->assertEquals(1, $panel->fresh()->curators->count());
    }

    /**
     * @test
     */
    public function panel_belongsToMany_coordinators()
    {
        $u = factory(User::class)->create();
        $u2 = factory(User::class)->create();
        $this->panel->users()->sync([$u->id => ['is_coordinator' => true], $u2->id => ['is_coordinator' => false]]);
        $panel = $this->panel->fresh();

        $this->assertEquals(1, $panel->fresh()->coordinators->count());
    }

    /**
     * @test
     */
    public function can_add_user_to_panel()
    {
        $u = factory(User::class)->create();
        $this->panel->addUser($u);
        $this->assertTrue($this->panel->fresh()->users->contains(function ($user) use ($u) {
            return $user->id == $u->id;
        }));

        $u2 = factory(User::class)->create();
        $this->panel->addUser($u2, ['is_curator'=>1, 'can_edit_topics'=>1]);
        $this->assertTrue($this->panel->fresh()->users->contains(function ($user) use ($u2) {
            dd($user->toArray());
            return $user->id == $u2->id && $user->pivot->can_edit_topics == 1 && $user->pivot->is_curator == 1;
        }));
    }
}
