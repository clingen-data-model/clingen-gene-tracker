<?php

namespace Tests\Unit\Actions\Curations;

use App\Actions\Curations\RecordCurationFieldEvent;
use App\Curation;
use App\CurationStatus;
use App\Curations\CurationField;
use Carbon\Carbon;
use Tests\TestCase;

/**
 * The idempotency contract, stated as tests.
 *
 * @group curations
 * @group curation-history
 */
class RecordCurationFieldEventTest extends TestCase
{
    private Curation $curation;

    public function setup(): void
    {
        parent::setup();
        Carbon::setTestNow('2020-01-01 12:34:56');
        $this->curation = factory(Curation::class)->create();
    }

    /**
     * Rule 1: the same source event applied twice is a no-op.
     *
     * @test
     */
    public function does_not_record_the_same_source_event_twice()
    {
        $status = CurationStatus::find(config('curations.statuses.curation-provisional'));

        $first = $this->record($status, '2020-02-01', 'gci', 'report-abc-2020-02-01');
        $second = $this->record($status, '2020-02-01', 'gci', 'report-abc-2020-02-01');

        $this->assertTrue($first);
        $this->assertFalse($second);
        $this->assertEquals(1, $this->historyCount($status));
    }

    /**
     * The key identifies the event, not the values it carries, so a replay whose
     * date has drifted is still recognised as already applied.
     *
     * @test
     */
    public function recognises_a_replayed_event_even_on_a_different_date()
    {
        $status = CurationStatus::find(config('curations.statuses.curation-provisional'));

        $this->record($status, '2020-02-01', 'gci', 'report-abc-2020-02-01');
        $again = $this->record($status, '2020-09-09', 'gci', 'report-abc-2020-02-01');

        $this->assertFalse($again);
        $this->assertEquals(1, $this->historyCount($status));
    }

    /**
     * Rule 2: a different event asserting the value the timeline already holds is
     * not recorded, however much later it arrives.
     *
     * @test
     */
    public function does_not_record_a_value_the_timeline_already_holds()
    {
        $status = CurationStatus::find(config('curations.statuses.curation-provisional'));

        $this->record($status, '2020-02-01', 'gci', 'key-one');
        $later = $this->record($status, '2020-06-01', 'gci', 'key-two');

        $this->assertFalse($later);
        $this->assertEquals(1, $this->historyCount($status));
    }

    /**
     * Rule 3: a genuinely different value is recorded even when it predates the
     * newest event -- but it must not displace the current value.
     *
     * @test
     */
    public function records_an_older_event_without_changing_the_current_value()
    {
        $provisional = CurationStatus::find(config('curations.statuses.curation-provisional'));
        $approved = CurationStatus::find(config('curations.statuses.curation-approved'));

        $this->record($approved, '2020-06-01', 'gci', 'newest');
        $this->assertEquals($approved->id, $this->curation->fresh()->curation_status_id);

        $backdated = $this->record($provisional, '2020-03-01', 'gci', 'backdated');

        $this->assertTrue($backdated);
        $this->assertEquals(1, $this->historyCount($provisional), 'the older event belongs in history');
        $this->assertEquals(
            $approved->id,
            $this->curation->fresh()->curation_status_id,
            'an older event must not displace the current value'
        );
    }

    /**
     * The database, not a PHP check, is what makes this safe under concurrency.
     *
     * @test
     */
    public function the_unique_index_rejects_a_duplicate_source_event()
    {
        $status = CurationStatus::find(config('curations.statuses.curation-provisional'));
        $this->record($status, '2020-02-01', 'gci', 'racing-key');

        // Simulates the losing side of a race, which sees no existing row on its
        // read and only discovers the conflict when it writes.
        $this->expectException(\Illuminate\Database\QueryException::class);

        \DB::table('curation_curation_status')->insert([
            'curation_id' => $this->curation->id,
            'curation_status_id' => config('curations.statuses.curation-approved'),
            'status_date' => '2020-05-05 00:00:00',
            'source' => 'gci',
            'source_event_key' => 'racing-key',
        ]);
    }

    private function record(CurationStatus $status, string $date, string $source, string $key): bool
    {
        return RecordCurationFieldEvent::run(
            $this->curation,
            CurationField::Status,
            $status->id,
            $date,
            $source,
            $key
        );
    }

    private function historyCount(CurationStatus $status): int
    {
        return \DB::table('curation_curation_status')
            ->where('curation_id', $this->curation->id)
            ->where('curation_status_id', $status->id)
            ->count();
    }
}
