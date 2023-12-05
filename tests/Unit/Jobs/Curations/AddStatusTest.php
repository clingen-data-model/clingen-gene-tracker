<?php

namespace Tests\Unit\Jobs\Curations;

use App\Curation;
use App\CurationStatus;
use App\Jobs\Curations\AddStatus;
use Carbon\Carbon;
use Tests\TestCase;

/**
 * @group curations
 */
class AddStatusTest extends TestCase
{
    public function setup(): void
    {
        parent::setup();
        Carbon::setTestNow('2020-01-01 12:34:56');
        $this->curation = factory(Curation::class)->create();
    }

    /**
     * @test
     */
    public function adds_status_with_status_date_today_if_status_date_not_specified(): void
    {
        $job = new AddStatus(
            $this->curation,
            CurationStatus::find(config('curations.statuses.curation-provisional'))
        );

        $job->handle();

        $this->assertDatabaseHas('curation_curation_status', [
            'curation_id' => $this->curation->id,
            'curation_status_id' => config('curations.statuses.curation-provisional'),
            'status_date' => Carbon::now()->startOfDay(),
        ]);
    }

    /**
     * @test
     */
    public function adds_status_to_status_history_with_date_if_specified(): void
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
            'status_date' => '2019-02-01 00:00:00',
        ]);
    }

    /**
     * @test
     */
    public function does_not_add_status_if_new_status_matches_current_status(): void
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
    public function does_not_add_previously_added_status_if_date_matches_existing_status_date(): void
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
    public function sets_curation_status_id_on_curation(): void
    {
        $job = new AddStatus(
            $this->curation,
            CurationStatus::find(config('project.curation-statuses.curation-provisional')),
            Carbon::now()->addDays(2)
        );

        $job->handle();

        $this->assertDatabaseHas('curations', [
            'id' => $this->curation->id,
            'curation_status_id' => config('project.curation-statuses.curation-provisional'),
        ]);
    }

    /**
     * @test
     */
    public function does_not_sets_curation_status_id_on_curation_if_status_date_greater_than_last_status_date(): void
    {
        $job = new AddStatus(
            $this->curation,
            CurationStatus::find(config('project.curation-statuses.curation-provisional')),
            '2019-01-01'
        );

        $job->handle();

        $this->assertDatabaseHas('curations', [
            'id' => $this->curation->id,
            'curation_status_id' => config('project.curation-statuses.uploaded'),
        ]);
    }
}
