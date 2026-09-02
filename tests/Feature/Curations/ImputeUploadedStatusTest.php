<?php

namespace Tests\Feature\Curations;

use App\Actions\Curations\ProjectCurationField;
use App\Actions\Curations\RecordCurationFieldEvent;
use App\Curation;
use App\Curations\CurationField;
use App\Curations\StatusTransitions;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * @group curations
 * @group curation-history
 */
class ImputeUploadedStatusTest extends TestCase
{
    private Curation $curation;

    public function setup(): void
    {
        parent::setup();
        Carbon::setTestNow('2020-06-01');
        $this->curation = factory(Curation::class)->create();
    }

    /**
     * @test
     */
    public function dates_the_imputed_row_from_created_at_when_that_precedes_the_history()
    {
        $this->historyStartingAt(config('curations.statuses.precuration'), '2020-09-01');

        $this->artisan('curations:impute-uploaded-status', ['curation' => $this->curation->id])
            ->assertSuccessful();

        $this->assertDatabaseHas('curation_curation_status', [
            'curation_id' => $this->curation->id,
            'curation_status_id' => StatusTransitions::INITIAL,
            'status_date' => '2020-06-01 00:00:00',
            'source' => 'imputed',
        ]);
    }

    /**
     * Roughly a third of these curations carry a status dated before the tracker
     * record existed, and created_at would place Uploaded after it.
     *
     * @test
     */
    public function falls_back_to_the_first_status_when_it_predates_creation()
    {
        $this->historyStartingAt(config('curations.statuses.precuration'), '2019-01-15');

        $this->artisan('curations:impute-uploaded-status', ['curation' => $this->curation->id])
            ->assertSuccessful();

        $this->assertDatabaseHas('curation_curation_status', [
            'curation_id' => $this->curation->id,
            'curation_status_id' => StatusTransitions::INITIAL,
            'status_date' => '2019-01-15 00:00:00',
            'source' => 'imputed',
        ]);
    }

    /**
     * @test
     */
    public function does_not_disturb_the_current_status()
    {
        $approved = config('curations.statuses.curation-approved');
        $this->historyStartingAt($approved, '2019-01-15');

        $this->artisan('curations:impute-uploaded-status', ['curation' => $this->curation->id])
            ->expectsOutputToContain('No curation changed its current status')
            ->assertSuccessful();

        $this->assertEquals($approved, $this->curation->fresh()->curation_status_id);
    }

    /**
     * @test
     */
    public function is_idempotent()
    {
        $this->historyStartingAt(config('curations.statuses.precuration'), '2020-09-01');

        $this->artisan('curations:impute-uploaded-status', ['curation' => $this->curation->id]);
        $this->artisan('curations:impute-uploaded-status', ['curation' => $this->curation->id]);

        $this->assertEquals(1, DB::table('curation_curation_status')
            ->where('curation_id', $this->curation->id)
            ->where('curation_status_id', StatusTransitions::INITIAL)
            ->count());
    }

    /**
     * @test
     */
    public function a_dry_run_writes_nothing()
    {
        $this->historyStartingAt(config('curations.statuses.precuration'), '2020-09-01');

        $this->artisan('curations:impute-uploaded-status', [
            'curation' => $this->curation->id,
            '--dry-run' => true,
        ])->assertSuccessful();

        $this->assertDatabaseMissing('curation_curation_status', [
            'curation_id' => $this->curation->id,
            'curation_status_id' => StatusTransitions::INITIAL,
        ]);
    }

    /**
     * @test
     */
    public function leaves_a_curation_that_already_has_the_row()
    {
        $this->artisan('curations:impute-uploaded-status', ['curation' => $this->curation->id])
            ->expectsOutputToContain('already has an')
            ->assertSuccessful();
    }

    /**
     * Reproduces the shape this command exists for: history that begins partway
     * through the workflow because the Uploaded row was never written.
     */
    private function historyStartingAt(int $statusId, string $date): void
    {
        RecordCurationFieldEvent::run(
            $this->curation,
            CurationField::Status,
            $statusId,
            $date,
            'test',
            'test:'.$statusId
        );

        DB::table('curation_curation_status')
            ->where('curation_id', $this->curation->id)
            ->where('curation_status_id', StatusTransitions::INITIAL)
            ->delete();

        // Re-project so the pointer reflects the remaining history. Without this the
        // fixture leaves drift behind and the command is measured against a curation
        // that is broken in a second, unrelated way.
        ProjectCurationField::run($this->curation, CurationField::Status);
        $this->curation->refresh();
    }
}
