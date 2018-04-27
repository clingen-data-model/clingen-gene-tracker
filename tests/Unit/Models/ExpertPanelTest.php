<?php

namespace Tests\Unit\models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
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

        $this->assertInstance(HasMany::class, $this->panel->topics());
    }
}
