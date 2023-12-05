<?php

namespace Tests\Unit\Http\Controllers\Api;

use App\WorkingGroup;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * @group api
 * @group working-groups
 */
class WorkingGroupControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        WorkingGroup::all()->each(function ($grp) {
            $grp->delete();
        });

        $this->user = factory(\App\User::class)->create();
        $this->groups = factory(\App\WorkingGroup::class, 20)->create();
        $this->group = $this->groups->first();
    }

    /**
     * @test
     */
    public function index_returns_all_working_groups(): void
    {
        $this->withoutExceptionHandling();
        $response = $this->actingAs($this->user, 'api')
            ->call('GET', '/api/working-groups/');
        $this->assertEquals($this->groups->toArray(), $response->original->toArray());
    }

    /**
     * @test
     */
    public function index_includes_expert_panels_if_requested(): void
    {
        $expertPanels = factory(\App\ExpertPanel::class, 3)->create(['working_group_id' => $this->group->id]);
        $response = $this->actingAs($this->user, 'api')
            ->call('GET', '/api/working-groups/?with=expertPanels')
            ->assertSee('expert_panels')
            ->assertSee($expertPanels->first()->name)
            ->assertSee($expertPanels->get(1)->name)
            ->assertSee($expertPanels->get(2)->name);
    }

    /**
     * @test
     */
    public function show_returns_ok_response(): void
    {
        $this->actingAs($this->user, 'api')
            ->call('GET', '/api/working-groups/'.$this->group->id)
            ->assertStatus(200);
    }

    /**
     * @test
     */
    public function show_returns_requested_group_info(): void
    {
        $this->actingAs($this->user, 'api')
            ->call('GET', '/api/working-groups/'.$this->group->id)
            ->assertSee($this->group->name);
    }

    /**
     * @test
     */
    public function show_includes_expert_panels_by_default(): void
    {
        $expertPanels = factory(\App\ExpertPanel::class, 3)->create(['working_group_id' => $this->group->id]);
        $this->group->load('expertPanels');
        $this->actingAs($this->user, 'api')
            ->call('GET', '/api/working-groups/'.$this->group->id)
            // ->assertJson($this->group->toArray())
            ->assertSee($expertPanels->first()->name);
    }

    /**
     * @test
     */
    public function show_includes_curations_for_expert_panels_by_default(): void
    {
        $expertPanels = factory(\App\ExpertPanel::class, 3)->create(['working_group_id' => $this->group->id]);
        $curations = factory(\App\Curation::class, 2)->create([
            'expert_panel_id' => $expertPanels->first()->id,
        ]);

        $this->actingAs($this->user, 'api')
            ->call('GET', '/api/working-groups/'.$this->group->id)
            ->assertJsonFragment(['name' => $expertPanels->first()->name]);
    }
}
