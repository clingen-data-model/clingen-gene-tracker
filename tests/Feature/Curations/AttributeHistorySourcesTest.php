<?php

namespace Tests\Feature\Curations;

use App\Affiliation;
use App\Curation;
use App\ExpertPanel;
use App\IncomingStreamMessage;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * @group curations
 * @group curation-history
 * @group gci
 */
#[\PHPUnit\Framework\Attributes\Group('curations')]
#[\PHPUnit\Framework\Attributes\Group('curation-history')]
#[\PHPUnit\Framework\Attributes\Group('gci')]
class AttributeHistorySourcesTest extends TestCase
{
    private const GDM_UUID = 'b6b0f6f2-2e6a-4a1f-9f0a-3a0b3f9f4d21';

    private const REPORT_ID = 'a1b2c3d4-0000-4444-8888-121212121212';

    private Curation $curation;

    private int $rowId;

    public function setup(): void
    {
        parent::setup();
        Carbon::setTestNow('2022-01-01');
        $this->curation = factory(Curation::class)->create(['gdm_uuid' => self::GDM_UUID]);
    }

    /**
     * @test
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function keys_a_status_row_to_the_message_that_asserted_it()
    {
        $this->message('approved', '2021-05-04T14:30:11.000Z', '2021-05-04T16:00:00.000Z');
        $status = config('curations.statuses.curation-approved');
        $this->legacyStatusRow($status, '2021-05-04 16:00:00');

        $this->artisan('curations:attribute-history-sources', ['curation' => $this->curation->id])
            ->assertSuccessful();

        $this->assertEquals('gci', $this->statusRow()->source);
        $this->assertEquals(self::REPORT_ID.'-2021-05-04T14:30:11.000Z', $this->statusRow()->source_event_key);
    }

    /**
     * @test
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function matches_a_row_restore_status_timestamps_stamped_with_the_emission_time()
    {
        // restore-status-timestamps writes the emission time, not status.date, so
        // a restored row has to match on that instead.
        $this->message('approved', '2021-05-04T14:30:11.000Z', '2021-05-04T16:00:00.000Z');
        $this->legacyStatusRow(config('curations.statuses.curation-approved'), '2021-05-04 14:30:11');

        $this->artisan('curations:attribute-history-sources', ['curation' => $this->curation->id])
            ->assertSuccessful();

        $this->assertEquals('gci', $this->statusRow()->source);
    }

    /**
     * @test
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function leaves_a_row_alone_when_only_the_day_matches()
    {
        // The truncated case: the message is the right one, but nothing has put
        // the time back yet, so the match is a guess.
        $this->message('approved', '2021-05-04T14:30:11.000Z', '2021-05-04T16:00:00.000Z');
        $this->legacyStatusRow(config('curations.statuses.curation-approved'), '2021-05-04 00:00:00');

        $this->artisan('curations:attribute-history-sources', ['curation' => $this->curation->id])
            ->assertSuccessful();

        $this->assertEquals('backfill', $this->statusRow()->source);
        $this->assertStringStartsWith('legacy:', $this->statusRow()->source_event_key);
    }

    /**
     * @test
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function keys_a_status_row_to_the_ui_when_a_revision_names_a_user()
    {
        $status = config('curations.statuses.curation-approved');
        $this->legacyStatusRow($status, '2021-05-04 09:15:00');
        $this->revision('curation_status_id', $status, '2021-05-04 09:15:00', userId: 7);

        $this->artisan('curations:attribute-history-sources', ['curation' => $this->curation->id])
            ->assertSuccessful();

        $this->assertEquals('ui', $this->statusRow()->source);
        $this->assertEquals('ui:status:2021-05-04:'.$status, $this->statusRow()->source_event_key);
    }

    /**
     * @test
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function ignores_a_revision_with_no_user()
    {
        // A null user_id is every queue and console path, which is not evidence of
        // anything -- least of all the UI.
        $status = config('curations.statuses.curation-approved');
        $this->legacyStatusRow($status, '2021-05-04 09:15:00');
        $this->revision('curation_status_id', $status, '2021-05-04 09:15:00', userId: null);

        $this->artisan('curations:attribute-history-sources', ['curation' => $this->curation->id])
            ->assertSuccessful();

        $this->assertEquals('backfill', $this->statusRow()->source);
    }

    /**
     * @test
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function leaves_a_row_alone_when_two_messages_assert_it_at_the_same_instant()
    {
        $this->message('approved', '2021-05-04T14:30:11.000Z', '2021-05-04T16:00:00.000Z');
        $this->message('approved', '2021-05-04T14:30:11.000Z', '2021-05-04T16:00:00.000Z', reportId: 'second-report');
        $this->legacyStatusRow(config('curations.statuses.curation-approved'), '2021-05-04 16:00:00');

        $this->artisan('curations:attribute-history-sources', ['curation' => $this->curation->id])
            ->assertSuccessful();

        $this->assertEquals('backfill', $this->statusRow()->source);
    }

    /**
     * @test
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function does_not_touch_a_row_that_already_names_its_source()
    {
        $this->message('approved', '2021-05-04T14:30:11.000Z', '2021-05-04T16:00:00.000Z');
        $this->legacyStatusRow(config('curations.statuses.curation-approved'), '2021-05-04 16:00:00');
        DB::table('curation_curation_status')
            ->where('id', $this->rowId)
            ->update(['source' => 'imputed', 'source_event_key' => 'impute-uploaded:'.$this->curation->id]);

        $this->artisan('curations:attribute-history-sources', ['curation' => $this->curation->id])
            ->assertSuccessful();

        $this->assertEquals('imputed', $this->statusRow()->source);
        $this->assertEquals('impute-uploaded:'.$this->curation->id, $this->statusRow()->source_event_key);
    }

    /**
     * @test
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function keys_an_ownership_row_to_the_transfer_that_caused_it()
    {
        $affiliation = factory(Affiliation::class)->create(['clingen_id' => '40001']);
        $expertPanel = factory(ExpertPanel::class)->create(['affiliation_id' => $affiliation->id]);
        $this->transferMessage($expertPanel, '2021-06-01T11:00:00.000Z');
        $rowId = DB::table('curation_expert_panel')->insertGetId([
            'curation_id' => $this->curation->id,
            'expert_panel_id' => $expertPanel->id,
            'start_date' => '2021-06-01 11:00:00',
            'source' => 'backfill',
            'source_event_key' => 'legacy:curation_expert_panel:1',
        ]);

        $this->artisan('curations:attribute-history-sources', ['curation' => $this->curation->id])
            ->assertSuccessful();

        $row = DB::table('curation_expert_panel')->where('id', $rowId)->first();
        $this->assertEquals('gci', $row->source);
        $this->assertEquals(self::REPORT_ID.'-2021-06-01T11:00:00.000Z', $row->source_event_key);
    }

    /**
     * @test
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function leaves_both_rows_alone_when_one_message_could_have_written_either()
    {
        // The message asserts the status at 16:00 and was emitted at 14:30, and
        // there is a row at each instant. Only one of them is the row it wrote.
        $this->message('approved', '2021-05-04T14:30:11.000Z', '2021-05-04T16:00:00.000Z');
        $status = config('curations.statuses.curation-approved');
        $this->legacyStatusRow($status, '2021-05-04 16:00:00');
        $first = $this->rowId;
        $this->legacyStatusRow($status, '2021-05-04 14:30:11');

        $this->artisan('curations:attribute-history-sources', ['curation' => $this->curation->id])
            ->assertSuccessful();

        $this->assertEquals('backfill', DB::table('curation_curation_status')->where('id', $first)->first()->source);
        $this->assertEquals('backfill', $this->statusRow()->source);
    }

    /**
     * @test
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function a_dry_run_writes_nothing()
    {
        $this->message('approved', '2021-05-04T14:30:11.000Z', '2021-05-04T16:00:00.000Z');
        $this->legacyStatusRow(config('curations.statuses.curation-approved'), '2021-05-04 16:00:00');

        $this->artisan('curations:attribute-history-sources', [
            'curation' => $this->curation->id,
            '--dry-run' => true,
        ])->assertSuccessful();

        $this->assertEquals('backfill', $this->statusRow()->source);
    }

    /**
     * @test
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function leaves_the_value_and_the_date_untouched()
    {
        $status = config('curations.statuses.curation-approved');
        $this->message('approved', '2021-05-04T14:30:11.000Z', '2021-05-04T16:00:00.000Z');
        $this->legacyStatusRow($status, '2021-05-04 16:00:00');
        $before = $this->curation->fresh()->curation_status_id;

        $this->artisan('curations:attribute-history-sources', ['curation' => $this->curation->id])
            ->assertSuccessful();

        $row = $this->statusRow();
        $this->assertEquals($status, $row->curation_status_id);
        $this->assertEquals('2021-05-04 16:00:00', (string) $row->status_date);
        $this->assertEquals($before, $this->curation->fresh()->curation_status_id);
    }

    private function statusRow()
    {
        return DB::table('curation_curation_status')->where('id', $this->rowId)->first();
    }

    /**
     * The curation factory's created hook already wrote a status row, so the row
     * under test is tracked by id rather than by position.
     */
    private function legacyStatusRow(int $statusId, string $date): void
    {
        $this->rowId = DB::table('curation_curation_status')->insertGetId([
            'curation_id' => $this->curation->id,
            'curation_status_id' => $statusId,
            'status_date' => $date,
            'source' => 'backfill',
            'source_event_key' => 'legacy:curation_curation_status:'.$statusId.':'.$date,
        ]);
    }

    private function revision(string $key, $newValue, string $at, ?int $userId): void
    {
        DB::table('revisions')->insert([
            'revisionable_type' => Curation::class,
            'revisionable_id' => $this->curation->id,
            'user_id' => $userId,
            'key' => $key,
            'old_value' => null,
            'new_value' => (string) $newValue,
            'created_at' => $at,
            'updated_at' => $at,
        ]);
    }

    private function message(string $status, string $messageDate, string $statusDate, string $reportId = self::REPORT_ID): void
    {
        $this->store([
            'report_id' => $reportId,
            'date' => $messageDate,
            'status' => ['name' => $status, 'date' => $statusDate],
        ]);
    }

    private function transferMessage(ExpertPanel $to, string $messageDate): void
    {
        $this->store([
            'report_id' => self::REPORT_ID,
            'date' => $messageDate,
            'content' => [
                'event_type' => 'transfer',
                'transfer_to' => ['gcep_id' => $to->affiliation->clingen_id],
            ],
        ]);
    }

    private function store(array $payload): void
    {
        $payload['gene_validity_evidence_level'] = [
            'evidence_level' => 'Definitive',
            'genetic_condition' => ['mode_of_inheritance' => 'Autosomal dominant inheritance'],
        ];

        IncomingStreamMessage::create([
            'topic' => 'gene_validity_events',
            'key' => $payload['report_id'].'-'.$payload['date'],
            'partition' => 0,
            'offset' => 0,
            'error_code' => 0,
            'gdm_uuid' => self::GDM_UUID,
            'payload' => json_decode(json_encode($payload)),
        ]);
    }
}
