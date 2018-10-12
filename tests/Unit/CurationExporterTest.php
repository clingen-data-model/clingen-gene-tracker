<?php

namespace Tests\Unit;

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
    
    public function setUp()
    {
        parent::setUp();
        \DB::table('curations')->delete();
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
                'Status',
                'Disease Entity'
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

        $c1->created_at = now()->subDays(10);
        $c1->save();
        $c2->created_at = now()->addDays(10);
        $c2->save();

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
        $c1->created_at = now()->subDays(10);
        $c1->save();

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
        $this->assertEquals('"Gene Symbol","Expert Panel",Curator,Status,"Disease Entity"', $content[0]);
        $this->assertEquals($this->curations->count()+1, count(array_filter($content)));

        unline($path);
    }
}
