<?php

namespace Tests\Unit\Jobs\Curations;

use App\Curation;
use Carbon\Carbon;
use Tests\TestCase;
use App\CurationStatus;
use App\Jobs\Curations\AddStatus;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group curations
 */
class AddStatusTest extends TestCase
{
    public function setup():void
    {
        parent::setup();
        Carbon::setTestNow('2020-01-01');
        $this->curation = factory(Curation::class)->create();
    }

    /**
     * @test
     */
    public function adds_status_to_status_history_with_date_if_specified()
    {
        $job = new AddStatus(
            $this->curation,
            CurationStatus::find(config('project.curation-statuses.curation-provisional')),
            '2019-01-01'
        );

        $job->handle();

        $this->assertDatabaseHas('curation_curation_status', [
            'curation_id' => $this->curation->id,
            'curation_status_id' => config('project.curation-statuses.curation-provisional'),
            'status_date' => '2019-01-01 00:00:00'
        ]);
    }

    /**
     * @test
     */
    public function does_not_add_status_if_new_status_matches_current_status()
    {
        Carbon::setTestNow('2020-01-15');
        AddStatus::dispatchNow(
            $this->curation->fresh(),
            CurationStatus::find(config('project.curation-statuses.curation-provisional'))
        );

        Carbon::setTestNow('2020-02-01');
        AddStatus::dispatchNow(
            $this->curation->fresh(),
            CurationStatus::find(config('project.curation-statuses.curation-provisional'))
        );
        
        $this->assertEquals(2, $this->curation->statuses()->count());
    }
    

    /**
     * @test
     */
    public function does_not_add_previously_added_status_if_date_matches_existing_status_date()
    {
        AddStatus::dispatchNow(
            $this->curation->fresh(),
            CurationStatus::find(config('project.curation-statuses.curation-provisional')),
            '2019-12-01'
        );

        AddStatus::dispatchNow(
            $this->curation->fresh(),
            CurationStatus::find(config('project.curation-statuses.curation-provisional')),
            '2019-12-01'
        );

        ($this->curation->statuses()->get()->toArray());

        $this->assertEquals(2, $this->curation->fresh()->statuses()->count());
    }

    /**
     * @test
     */
    public function sets_curation_status_id_on_curation()
    {
        $job = new AddStatus(
            $this->curation,
            CurationStatus::find(config('project.curation-statuses.curation-provisional')),
            '2019-01-01'
        );

        $job->handle();

        $this->assertDatabaseHas('curations', [
            'id' => $this->curation->id,
            'curation_status_id' => config('project.curation-statuses.curation-provisional')
        ]);
    }
}
