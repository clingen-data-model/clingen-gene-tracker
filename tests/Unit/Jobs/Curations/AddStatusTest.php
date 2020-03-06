<?php

namespace Tests\Unit\Jobs\Curations;

use App\Curation;
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
            '2020-01-01'
            );

        $job->handle();

        $this->assertDatabaseHas('curation_curation_status', [
            'curation_id' => $this->curation->id,
            'curation_status_id' => config('project.curation-statuses.curation-provisional'),
            'status_date' => '2020-01-01 00:00:00'
        ]);
    }
}
