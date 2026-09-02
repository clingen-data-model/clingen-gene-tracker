<?php

namespace Tests\Feature\Curations;

use App\Actions\Curations\RecordCurationFieldEvent;
use App\Curation;
use App\CurationStatus;
use App\Curations\CurationField;
use App\ExpertPanel;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Derived data is rebuildable from history. This is what replaces
 * curations:order-statuses, curations:set_current_status_id and
 * curations:clean-statuses -- rather than a new repair command per symptom.
 *
 * @group curations
 * @group curation-history
 */
class RebuildProjectionsTest extends TestCase
{
    private Curation $curation;

    public function setup(): void
    {
        parent::setup();
        Carbon::setTestNow('2020-01-01');
        $this->curation = factory(Curation::class)->create();
    }

    /**
     * @test
     */
    public function repairs_a_current_status_that_disagrees_with_history()
    {
        $approved = CurationStatus::find(config('curations.statuses.curation-approved'));
        $this->recordStatus($approved, '2021-01-01', 'one');

        DB::table('curations')->where('id', $this->curation->id)
            ->update(['curation_status_id' => config('curations.statuses.uploaded')]);

        $this->artisan('curations:rebuild-projections', ['curation' => $this->curation->id])
            ->assertSuccessful();

        $this->assertEquals($approved->id, $this->curation->fresh()->curation_status_id);
    }

    /**
     * @test
     */
    public function repairs_ownership_intervals()
    {
        [$a, $b] = factory(ExpertPanel::class, 2)->create();
        $this->recordOwner($a, '2021-01-01', 'one');
        $this->recordOwner($b, '2021-06-01', 'two');

        // The shape SetOwner used to leave behind: nothing open, and an end date
        // taken from when the row happened to be written.
        DB::table('curation_expert_panel')->where('curation_id', $this->curation->id)
            ->update(['end_date' => '2026-01-01']);

        $this->artisan('curations:rebuild-projections', ['curation' => $this->curation->id])
            ->assertSuccessful();

        $rows = DB::table('curation_expert_panel')
            ->where('curation_id', $this->curation->id)
            ->orderBy('start_date')->orderBy('id')->get();

        $this->assertNull($rows->last()->end_date, 'the current owner must be open ended');
        $this->assertEquals('2021-06-01 00:00:00', $rows->get($rows->count() - 2)->end_date);
    }

    /**
     * @test
     */
    public function dry_run_reports_drift_without_writing()
    {
        $approved = CurationStatus::find(config('curations.statuses.curation-approved'));
        $this->recordStatus($approved, '2021-01-01', 'one');

        DB::table('curations')->where('id', $this->curation->id)
            ->update(['curation_status_id' => config('curations.statuses.uploaded')]);

        $this->artisan('curations:rebuild-projections', [
            'curation' => $this->curation->id,
            '--dry-run' => true,
        ])->assertSuccessful();

        $this->assertEquals(
            config('curations.statuses.uploaded'),
            $this->curation->fresh()->curation_status_id,
            'a dry run must not write'
        );
    }

    /**
     * @test
     */
    public function rejects_an_unknown_field()
    {
        $this->artisan('curations:rebuild-projections', ['--field' => 'nonsense'])->assertFailed();
    }

    private function recordStatus(CurationStatus $status, string $date, string $key): void
    {
        RecordCurationFieldEvent::run(
            $this->curation,
            CurationField::Status,
            $status->id,
            $date,
            'test',
            $key
        );
    }

    private function recordOwner(ExpertPanel $panel, string $date, string $key): void
    {
        RecordCurationFieldEvent::run(
            $this->curation,
            CurationField::ExpertPanel,
            $panel->id,
            $date,
            'test',
            $key
        );
    }
}
