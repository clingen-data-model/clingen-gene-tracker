<?php

namespace Tests\Unit;

use App\User;
use App\Curation;
use Tests\TestCase;
use App\ExpertPanel;
use App\WorkingGroup;
use App\CurationExporter;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class CurationExporterTest extends TestCase
{
    use DatabaseTransactions;
    
    public function setUp(): void
    {
        parent::setUp();
        \DB::table('curations')->delete();
        $programmer = factory(User::class)->create()->assignRole('programmer');
        $this->actingAs($programmer);
        $this->groups = factory(WorkingGroup::class, 3)->create();
        $this->panels = collect();
        $this->groups->each(function ($grp) {
            $this->panels = $this->panels->merge($grp->expertPanels()->saveMany(factory(ExpertPanel::class, 3)->make()));
        });

        $this->curations = collect();
        $this->panels->each(function ($pnl) {
            $this->curations = $this->curations->merge($pnl->curations()->saveMany(factory(Curation::class, 3)->make()));
        });

        $this->exporter = new CurationExporter();
    }

    /**
     * @test
     */
    public function exporter_returns_all_curations_with_columns()
    {
        $curationData = $this->exporter->getData();

        $this->assertEquals(
            [
                'Gene Symbol',
                'Expert Panel',
                'Curator',
                'Disease Entity',
                'Uploaded date',
                'Precuration date',
                'Disease entity assigned date',
                'Curation In Progress date',
                'Curation Provisional date',
                'Curation Approved date',
                'Recuration assigned date',
                'Retired Assignment date',
                'Published date',
                'Created'
            ],
            array_keys($curationData->first())
        );

        $this->assertEquals($this->curations->count(), $curationData->count());
    }

    /**
     * @test
     */
    public function filters_by_expert_panel()
    {
        $curationData = $this->exporter->getData(['expert_panel_id' => $this->panels->first()->id]);

        $this->assertEquals(3, $curationData->count());
    }


    /**
     * @test
     */
    public function filters_by_date_range()
    {
        $c1 = $this->curations->first();
        $c2 = $this->curations->last();

        $c1->statuses()->updateExistingPivot(1, ['status_date' => now()->subDays(10)]);
        $c1 = $c1->fresh();
        $c2->statuses()->updateExistingPivot(1, ['status_date' => now()->addDays(10)]);
        $c2 = $c2->fresh();

        $rangeData1 = $this->exporter->getData(['start_date' => today()->subDays(10), 'end_date' => today()->subDays(9)]);
        $this->assertEquals(1, $rangeData1->count());

        $rangeData1 = $this->exporter->getData(['start_date' => today()->subDays(1), 'end_date' => today()->addDays(10)]);
        $this->assertEquals(26, $rangeData1->count());
    }
    
    /**
     * @test
     */
    public function filters_by_date_range_and_expert_panel_id()
    {
        $c1 = $this->curations->first();
        $c1->statuses()->updateExistingPivot(1, ['status_date' => now()->subDays(10)]);
        $c1 = $c1->fresh();

        $data = $this->exporter->getData(['expert_panel_id' => $this->panels->first()->id, 'start_date' => today()->subDays(2)]);
        $this->assertEquals(2, $data->count());
    }

    /**
     * @test
     */
    public function creates_a_csv_file_with_data()
    {
        $path = $this->exporter->getCsv();

        $this->assertFileExists($path);
        
        $content = explode("\n", file_get_contents($path));
        $this->assertEquals('"Gene Symbol","Expert Panel",Curator,"Disease Entity","Uploaded date","Precuration date","Disease entity assigned date","Curation In Progress date","Curation Provisional date","Curation Approved date","Recuration assigned date","Retired Assignment date","Published date",Created', $content[0]);
        $this->assertEquals($this->curations->count()+1, count(array_filter($content)));

        unlink($path);
    }

    /**
     * @test
     */
    public function coordinators_see_all_curations()
    {
        $coordinator = factory(User::class)->create();
        $coordinator->expertPanels()->attach([
            $this->panels->random()->id => [
                'is_coordinator' => 1
            ]
        ]);
        $this->actingAs($coordinator);

        $data = $this->exporter->getData();

        $this->assertEquals($this->curations->count(), $data->count());
    }
    
    /**
     * @test
     */
    public function admins_see_all_curations()
    {
        $admin = factory(User::class)->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $data = $this->exporter->getData();

        $this->assertEquals($this->curations->count(), $data->count());
    }

    /**
     * @test
     */
    public function curators_only_see_their_curations()
    {
        $curator = factory(User::class)->create();
        $curator->expertPanels()->attach([
            $this->panels->random()->id => [
                'is_coordinator' => 0,
                'is_curator' => 1
            ]
        ]);
        $this->actingAs($curator);

        $curation = Curation::create([
            'curator_id' => $curator->id,
            'gene_symbol' => 'beans'
        ]);

        $data = $this->exporter->getData();

        $this->assertEquals(1, $data->count());
    }
}
