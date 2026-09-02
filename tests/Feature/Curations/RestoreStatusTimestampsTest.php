<?php

namespace Tests\Feature\Curations;

use App\Curation;
use App\IncomingStreamMessage;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * @group curations
 * @group curation-history
 * @group gci
 */
class RestoreStatusTimestampsTest extends TestCase
{
    private const GDM_UUID = '0c861e10-78a7-4ebc-ac57-853fb16f94c9';

    private Curation $curation;

    public function setup(): void
    {
        parent::setup();
        Carbon::setTestNow('2020-01-01');
        $this->curation = factory(Curation::class)->create(['gdm_uuid' => self::GDM_UUID]);
    }

    /**
     * @test
     */
    public function takes_the_time_from_the_message_that_announced_the_status()
    {
        $this->message('approved', '2021-05-04T14:30:11.000Z', '2021-05-04T16:00:00.000Z');
        $this->midnightRow(config('curations.statuses.curation-approved'), '2021-05-04');

        $this->artisan('curations:restore-status-timestamps', ['curation' => $this->curation->id])
            ->assertSuccessful();

        // The emission time, not status.date -- GCI fills status.date for approved
        // messages with a synthetic 16:00:00Z that sorts after the publish that
        // really followed it.
        $this->assertEquals('2021-05-04 14:30:11', $this->statusDate());
    }

    /**
     * @test
     */
    public function leaves_a_backdated_status_at_midnight()
    {
        // Announced in 2021, but the panel dates the decision to 2011. The day is
        // all we know; there is no time anywhere for it.
        $this->message('approved', '2021-05-04T14:30:11.000Z', '2011-03-01T17:00:00.000Z');
        $this->midnightRow(config('curations.statuses.curation-approved'), '2011-03-01');

        $this->artisan('curations:restore-status-timestamps', ['curation' => $this->curation->id])
            ->assertSuccessful();

        $this->assertEquals('2011-03-01 00:00:00', $this->statusDate());
    }

    /**
     * Timing some rows of a day and not others reorders them, because the ones left
     * at midnight sort to the front regardless of when they happened.
     *
     * @test
     */
    public function leaves_a_day_alone_when_one_of_its_rows_cannot_be_timed()
    {
        $this->message('approved', '2021-05-04T14:30:11.000Z', '2021-05-04T14:30:11.000Z');
        $this->midnightRow(config('curations.statuses.curation-approved'), '2021-05-04');
        // Entered by hand the next day and backdated: no message, and created_at
        // falls on a different day, so no time is available.
        $this->midnightRow(config('curations.statuses.published'), '2021-05-04', '2021-05-05 09:00:00');

        $this->artisan('curations:restore-status-timestamps', ['curation' => $this->curation->id])
            ->expectsOutputToContain('day had a row we could not time')
            ->assertSuccessful();

        $this->assertEquals(
            ['2021-05-04 00:00:00', '2021-05-04 00:00:00'],
            DB::table('curation_curation_status')
                ->where('curation_id', $this->curation->id)
                ->whereIn('curation_status_id', [
                    config('curations.statuses.curation-approved'),
                    config('curations.statuses.published'),
                ])
                ->orderBy('id')->pluck('status_date')->all()
        );
    }

    /**
     * @test
     */
    public function falls_back_to_the_row_write_time_when_it_is_the_same_day()
    {
        $this->midnightRow(config('curations.statuses.precuration'), '2021-05-04', '2021-05-04 11:22:33');

        $this->artisan('curations:restore-status-timestamps', ['curation' => $this->curation->id])
            ->assertSuccessful();

        $this->assertEquals('2021-05-04 11:22:33', $this->statusDate(config('curations.statuses.precuration')));
    }

    /**
     * @test
     */
    public function a_dry_run_writes_nothing()
    {
        $this->midnightRow(config('curations.statuses.precuration'), '2021-05-04', '2021-05-04 11:22:33');

        $this->artisan('curations:restore-status-timestamps', [
            'curation' => $this->curation->id,
            '--dry-run' => true,
        ])->assertSuccessful();

        $this->assertEquals('2021-05-04 00:00:00', $this->statusDate(config('curations.statuses.precuration')));
    }

    private function statusDate(?int $statusId = null): string
    {
        return DB::table('curation_curation_status')
            ->where('curation_id', $this->curation->id)
            ->where('curation_status_id', $statusId ?? config('curations.statuses.curation-approved'))
            ->value('status_date');
    }

    private function midnightRow(int $statusId, string $date, ?string $writtenAt = null): void
    {
        DB::table('curation_curation_status')->insert([
            'curation_id' => $this->curation->id,
            'curation_status_id' => $statusId,
            'status_date' => $date.' 00:00:00',
            'source' => 'backfill',
            'source_event_key' => 'legacy:test:'.$statusId.':'.$date,
            'created_at' => $writtenAt ?? '2021-06-01 09:00:00',
            'updated_at' => $writtenAt ?? '2021-06-01 09:00:00',
        ]);
    }

    private function message(string $status, string $emitted, string $statusDate): void
    {
        IncomingStreamMessage::create([
            'topic' => 'gene_validity_events',
            'key' => self::GDM_UUID.'-'.$emitted,
            'partition' => 0,
            'offset' => 0,
            'error_code' => 0,
            'gdm_uuid' => self::GDM_UUID,
            'payload' => (object) [
                'report_id' => self::GDM_UUID,
                'date' => $emitted,
                'status' => (object) ['name' => $status, 'date' => $statusDate],
                'gene_validity_evidence_level' => (object) ['evidence_level' => 'Definitive'],
            ],
        ]);
    }
}
