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
        Carbon::setTestNow('2020-01-01 12:34:56');
        $this->curation = factory(Curation::class)->create();
    }


    /**
     * @test
     */
    public function adds_status_with_the_current_time_if_status_date_not_specified()
    {
        $job = new AddStatus(
            $this->curation,
            CurationStatus::find(config('curations.statuses.curation-provisional'))
        );

        $job->handle();

        $this->assertDatabaseHas('curation_curation_status', [
            'curation_id' => $this->curation->id,
            'curation_status_id' => config('curations.statuses.curation-provisional'),
            'status_date' => Carbon::now()
        ]);
    }
    

    /**
     * @test
     */
    public function keeps_the_time_of_a_status_date_it_is_given()
    {
        $job = new AddStatus(
            $this->curation,
            CurationStatus::find(config('project.curation-statuses.curation-provisional')),
            '2019-02-01 12:32:12'
        );

        $job->handle();

        $this->assertDatabaseHas('curation_curation_status', [
            'curation_id' => $this->curation->id,
            'curation_status_id' => config('project.curation-statuses.curation-provisional'),
            // Kept to the second: a status date that carries a time is a fact about
            // when the status changed, and truncating it loses the ordering of two
            // changes made on the same day.
            'status_date' => '2019-02-01 12:32:12'
        ]);
    }

    /**
     * @test
     */
    public function does_not_add_status_if_new_status_matches_current_status()
    {
        Carbon::setTestNow('2020-01-15');
        AddStatus::dispatchSync(
            $this->curation->fresh(),
            CurationStatus::find(config('project.curation-statuses.curation-provisional'))
        );

        Carbon::setTestNow('2020-02-01');
        AddStatus::dispatchSync(
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
        AddStatus::dispatchSync(
            $this->curation->fresh(),
            CurationStatus::find(config('project.curation-statuses.curation-provisional')),
            '2019-12-01'
        );

        AddStatus::dispatchSync(
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
            Carbon::now()->addDays(2)
        );

        $job->handle();

        $this->assertDatabaseHas('curations', [
            'id' => $this->curation->id,
            'curation_status_id' => config('project.curation-statuses.curation-provisional')
        ]);
    }

    /**
     * @test
     */
    public function does_not_sets_curation_status_id_on_curation_if_status_date_greater_than_last_status_date()
    {
        $job = new AddStatus(
            $this->curation,
            CurationStatus::find(config('project.curation-statuses.curation-provisional')),
            '2019-01-01'
        );

        $job->handle();

        $this->assertDatabaseHas('curations', [
            'id' => $this->curation->id,
            'curation_status_id' => config('project.curation-statuses.uploaded')
        ]);
    }
}
