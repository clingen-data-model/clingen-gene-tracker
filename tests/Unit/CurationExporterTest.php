<?php

namespace Tests\Unit;

use App\Classification;
use App\User;
use App\Curation;
use Carbon\Carbon;
use Tests\TestCase;
use App\ExpertPanel;
use App\WorkingGroup;
use App\CurationStatus;
use App\CurationExporter;
use App\Jobs\Curations\AddStatus;
use App\Jobs\Curations\AddClassification;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class CurationExporterTest extends TestCase
{
    use DatabaseTransactions;

    private $groups, $panels, $curations, $exporter;

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
    public function exporter_returns_the_correct_columns()
    {
        $csvPath = $this->exporter->getCsv();
        $file = fopen($csvPath, 'r');
        $firstLine = fgetcsv($file);

        $this->assertEquals(
            [
                'Gene Symbol',
                'Expert Panel',
                'Curator',
                'Disease Entity',
                'Curation Type',
                'Rationales',
                'Uploaded Date',
                'Precuration Date',
                'Disease entity assigned Date',
                'Precuration Complete Date',
                'Curation Provisional Date',
                'Curation Approved Date',
                'Recuration assigned Date',
                'Retired Assignment Date',
                'Published Date',
                'Unublished on GCI Date',
                'Classification',
                'Created',
                'GCI UUID',
            ],
            $firstLine
        );
    }
    
    /**
     * @test
     */
    public function returns_line_for_each_curation()
    {
        $csvPath = $this->exporter->getCsv();
        
        $this->assertFileHasLineCount($csvPath, $this->curations->count()+1);
    }

    /**
     * @test
     */
    public function filters_by_expert_panel()
    {
        $csvPath = $this->exporter->getCsv(['expert_panel_id' => $this->panels->first()->id]);

        $this->assertFileHasLineCount($csvPath, 4);
    }

    /**
     * @test
     */
    public function filters_export_by_date_range()
    {
        $c1 = $this->curations->first();
        $c2 = $this->curations->last();

        $c1->statuses()->updateExistingPivot(1, ['status_date' => now()->subDays(10)]);
        $c1 = $c1->fresh();
        $c2->statuses()->updateExistingPivot(1, ['status_date' => now()->addDays(10)]);
        $c2 = $c2->fresh();    

        $csvPath1 = $this->exporter->getCsv(['start_date' => today()->subDays(10), 'end_date' => today()->subDays(9)]);
        $this->assertFileHasLineCount($csvPath1, 2);

        $csvPath2 = $this->exporter->getCsv(['start_date' => today()->subDays(1), 'end_date' => today()->addDays(10)]);
        $this->assertFileHasLineCount($csvPath2, 27);
    }

    /**
     * @test
     */
    public function filters_by_date_range_and_expert_panel_id()
    {
        $c1 = $this->curations->first();
        $c1->statuses()->updateExistingPivot(1, ['status_date' => now()->subDays(10)]);
        $c1 = $c1->fresh();

        $filePath = $this->exporter->getCsv(['expert_panel_id' => $this->panels->first()->id, 'start_date' => today()->subDays(2)]);
        $this->assertFileHasLineCount($filePath, 3);
    }

    /**
     * @test
     */
    public function creates_a_csv_file_with_data()
    {
        $path = $this->exporter->getCsv();

        $this->assertFileExists($path);

        $content = explode("\n", file_get_contents($path));
        $this->assertEquals('"Gene Symbol","Expert Panel",Curator,"Disease Entity","Curation Type",Rationales,"Uploaded Date","Precuration Date","Disease entity assigned Date","Precuration Complete Date","Curation Provisional Date","Curation Approved Date","Recuration assigned Date","Retired Assignment Date","Published Date","Unublished on GCI Date",Classification,Created,"GCI UUID"', $content[0]);
        $this->assertEquals($this->curations->count() + 1, count(array_filter($content)));

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
                'is_coordinator' => 1,
            ],
        ]);
        $this->actingAs($coordinator);

        $csvPath = $this->exporter->getCsv();

        $this->assertFileHasLineCount($csvPath, $this->curations->count()+1);
    }

    /**
     * @test
     */
    public function admins_see_all_curations()
    {
        $admin = factory(User::class)->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $csvPath = $this->exporter->getCsv();

        $this->assertFileHasLineCount($csvPath, $this->curations->count()+1);
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
                'is_curator' => 1,
            ],
        ]);
        $this->actingAs($curator);

        $curation = factory(Curation::class)->create([
            'curator_id' => $curator->id,
            'gene_symbol' => 'beans',
        ]);

        $csvPath = $this->exporter->getCsv();

        $this->assertFileHasLineCount($csvPath, 2);
    }

    /**
     * @test
     */
    public function selects_latest_date_for_any_status()
    {
        $curation = factory(Curation::class)->create();
        CurationStatus::all()
            ->each(function ($st) use ($curation) {
                AddStatus::dispatch($curation, $st, Carbon::now());
                AddStatus::dispatch($curation, $st, Carbon::now()->subDays(1));
                AddStatus::dispatch($curation, $st, Carbon::now()->subDays(2));
            });            

        $csvPath = $this->exporter->getCsv(['expert_panel_id' => $curation->expert_panel_id]);
        $fh = fopen($csvPath, 'r');
        $header = fgetcsv($fh);
        $firstRow = fgetcsv($fh);
        
        $rowDict = array_combine($header, $firstRow);

        $dateFields = [
            "Uploaded Date",
            "Precuration Date",
            "Disease entity assigned Date",
            "Precuration Complete Date",
            "Curation Provisional Date",
            "Curation Approved Date",
            "Recuration assigned Date",
            "Retired Assignment Date",
            "Published Date",
            "Unublished on GCI Date",
        ];
            
        foreach($dateFields as $dateKey) {
            $this->assertEquals(Carbon::today()->format('Y-m-d'), $rowDict[$dateKey]);
        }
    }

    /**
     * @test
     */
    public function selects_latest_classification_for_curation()
    {
        $curation = factory(Curation::class)->create();
        list($class1, $class2) = Classification::find([1,2]);

        $date = Carbon::now();
        AddClassification::dispatch($curation, $class1, $date);
        AddClassification::dispatch($curation, $class2, $date);

        $csvPath = $this->exporter->getCsv(['expert_panel_id' => $curation->expert_panel_id]);
        $fh = fopen($csvPath, 'r');
        $header = fgetcsv($fh);
        $firstRow = fgetcsv($fh);
        
        $rowDict = array_combine($header, $firstRow);

        $this->assertEquals($class2->name, $rowDict['Classification']);
    }

    private function assertFileHasLineCount($filePath, $expected)
    {
        $fh = fopen($filePath, 'r');
        fgetcsv($fh);

        $lineCount = 0;
        while (!feof($fh)) {
            fgets($fh);
            $lineCount++;
        }

        $this->assertEquals($expected, $lineCount);

    }
    
}
