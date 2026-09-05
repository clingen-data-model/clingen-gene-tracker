<?php

namespace Tests\Feature\Gci;

use App\Affiliation;
use App\Curation;
use App\DataExchange\Contracts\GeneValidityCurationUpdateJob;
use App\ExpertPanel;
use App\Gci\GciMessage;
use App\Gene;
use App\IncomingStreamMessage;
use App\Jobs\ReplayGciEventsForCuration;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * The acceptance tests for replay safety.
 *
 * Replaying a GCI message that has already been applied must change nothing: no
 * new history rows, no altered current values, no outgoing stream messages and no
 * mail. And because Kafka gives no ordering guarantee across a redelivery, the
 * order the messages arrive in must not change where the curation ends up.
 *
 * @group gci
 * @group curations
 * @group curation-history
 */
#[\PHPUnit\Framework\Attributes\Group('gci')]
#[\PHPUnit\Framework\Attributes\Group('curations')]
#[\PHPUnit\Framework\Attributes\Group('curation-history')]
class ReplayGciEventsTest extends TestCase
{
    private const GDM_UUID = '0c861e10-78a7-4ebc-ac57-853fb16f94c9';

    /** Messages for one GDM, oldest first. */
    private array $fixtures = [
        'approved.json',
        'disease_change.json',
        'gdm_transfered.json',
    ];

    private ExpertPanel $originalOwner;

    public function setup(): void
    {
        parent::setup();

        // Curations must predate the fixtures, or every message is backdated and the
        // ordering this test is about never comes into play.
        Carbon::setTestNow('2019-01-01');

        factory(Gene::class)->create(['gene_symbol' => 'DICER1', 'hgnc_id' => 17098]);

        foreach (['40001', '40002'] as $clingenId) {
            $affiliation = factory(Affiliation::class)->create(['clingen_id' => $clingenId]);
            $panel = factory(ExpertPanel::class)->create(['affiliation_id' => $affiliation->id]);

            if ($clingenId === '40001') {
                $this->originalOwner = $panel;
            }
        }
    }

    /**
     * @test
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function replaying_a_curations_events_changes_nothing_the_second_time()
    {
        $curation = $this->makeCuration();
        $this->storeMessages();

        ReplayGciEventsForCuration::dispatchSync($curation);

        Mail::fake();
        $before = $this->snapshot($curation);

        for ($i = 0; $i < 5; $i++) {
            ReplayGciEventsForCuration::dispatchSync($curation);
        }

        $this->assertEquals($before, $this->snapshot($curation), 'replay must be a no-op');
        Mail::assertNothingSent();
    }

    /**
     * @test
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function replaying_does_not_produce_new_outgoing_stream_messages()
    {
        $curation = $this->makeCuration();
        $this->storeMessages();

        ReplayGciEventsForCuration::dispatchSync($curation);
        $sent = DB::table('stream_messages')->count();

        ReplayGciEventsForCuration::dispatchSync($curation);

        $this->assertEquals(
            $sent,
            DB::table('stream_messages')->count(),
            'a replay that changes nothing must not announce a change'
        );
    }

    /**
     * The watermark records what we have consumed. It changes on every message, so
     * writing it through the model would announce a precuration change to the data
     * exchange for each one, whether or not the curation actually changed.
     *
     * @test
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function advancing_the_watermark_does_not_announce_a_change()
    {
        $curation = $this->makeCuration();
        [$message] = $this->messages();

        $this->apply($curation, $message);
        $this->assertNotNull($curation->fresh()->gci_event_watermark, 'the watermark must still advance');

        $curation->fresh()->forceFill(['gci_event_watermark' => null])->saveQuietly();
        $sent = DB::table('stream_messages')->count();

        // Nothing about the curation differs now; only the watermark moves.
        $this->apply($curation, $message);

        $this->assertEquals($sent, DB::table('stream_messages')->count());
        $this->assertNotNull($curation->fresh()->gci_event_watermark);
    }

    /**
     * The property that makes replay genuinely safe rather than merely quiet.
     *
     * @test
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function messages_applied_in_reverse_order_reach_the_same_state()
    {
        $forwards = $this->makeCuration();
        $backwards = $this->makeCuration();

        foreach ($this->messages() as $message) {
            $this->apply($forwards, $message);
        }

        foreach (array_reverse($this->messages()) as $message) {
            $this->apply($backwards, $message);
        }

        $state = $this->projectedState($forwards);

        // Guard against passing vacuously: the messages must actually have moved the
        // curation off the state it was created in.
        $this->assertNotEquals($this->originalOwner->id, $state['expert_panel_id']);
        $this->assertNotNull($state['curation_status_id']);

        $this->assertEquals(
            $state,
            $this->projectedState($backwards),
            'arrival order must not change where the curation ends up'
        );
    }

    private function makeCuration(): Curation
    {
        return factory(Curation::class)->create([
            'gene_symbol' => 'DICER1',
            'hgnc_id' => 17098,
            'mondo_id' => 'MONDO:0011111',
            'gdm_uuid' => self::GDM_UUID,
            // Both curations must start from the same owner for their end states to
            // be comparable.
            'expert_panel_id' => $this->originalOwner->id,
        ]);
    }

    /** @return GciMessage[] */
    private function messages(): array
    {
        return array_map(
            fn ($file) => new GciMessage(file_get_contents(base_path('tests/files/gci_messages/'.$file))),
            $this->fixtures
        );
    }

    private function storeMessages(): void
    {
        foreach ($this->messages() as $message) {
            IncomingStreamMessage::create([
                'topic' => 'gene_validity_events',
                'key' => $message->sourceKey,
                'partition' => 0,
                'offset' => 0,
                'error_code' => 0,
                'payload' => $message->payload,
                'gdm_uuid' => $message->uuid,
            ]);
        }
    }

    private function apply(Curation $curation, GciMessage $message): void
    {
        $job = app()->makeWith(GeneValidityCurationUpdateJob::class, [
            'curation' => $curation->fresh(),
            'gciMessage' => $message,
        ]);

        dispatch_sync($job);
    }

    /**
     * Everything this curation's history and derived data consists of.
     */
    private function snapshot(Curation $curation): array
    {
        return [
            'curation' => $this->projectedState($curation),
            'statuses' => $this->rows('curation_curation_status', $curation),
            'classifications' => $this->rows('classification_curation', $curation),
            'expert_panels' => $this->rows('curation_expert_panel', $curation),
            'notes' => DB::table('notes')
                ->where('subject_type', Curation::class)
                ->where('subject_id', $curation->id)
                ->count(),
        ];
    }

    private function projectedState(Curation $curation): array
    {
        $fresh = $curation->fresh();

        return [
            'curation_status_id' => $fresh->curation_status_id,
            'expert_panel_id' => $fresh->expert_panel_id,
            'mondo_id' => $fresh->mondo_id,
            'affiliation_id' => $fresh->affiliation_id,
            'moi_id' => $fresh->moi_id,
            'current_classification' => $fresh->currentClassification->id,
        ];
    }

    private function rows(string $table, Curation $curation): array
    {
        return DB::table($table)
            ->where('curation_id', $curation->id)
            ->orderBy('id')
            ->get()
            ->map(fn ($r) => collect((array) $r)->except(['id', 'created_at', 'updated_at'])->all())
            ->all();
    }
}
