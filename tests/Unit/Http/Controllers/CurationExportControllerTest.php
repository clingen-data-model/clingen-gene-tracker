<?php

namespace Tests\Unit;

use App\User;
use App\Curation;
use Tests\TestCase;
use App\ExpertPanel;
use App\WorkingGroup;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CurationExportControllerTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
        \DB::table('curations')->delete();
        $this->user = factory(User::class)->create();
        $this->groups = factory(WorkingGroup::class, 3)->create();
        $this->panels = collect();
        $this->groups->each(function ($grp) {
            $this->panels = $this->panels->merge($grp->expertPanels()->saveMany(factory(ExpertPanel::class, 3)->make()));
        });

        $this->curations = collect();
        $this->panels->each(function ($pnl) {
            $this->curations = $this->curations->merge($pnl->curations()->saveMany(factory(Curation::class, 3)->make()));
        });

        $this->actingAs($this->user);
    }

    /**
     * @test
     */
    public function it_can_download_a_csv()
    {
        $response = $this->call('get', route('curations.export.download'));
        $response->assertStatus(200);
    }
}
