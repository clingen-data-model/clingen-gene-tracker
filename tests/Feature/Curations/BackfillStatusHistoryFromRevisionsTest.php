<?php

namespace Tests\Feature\Curations;

use App\Curation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * @group curations
 * @group curation-history
 */
class BackfillStatusHistoryFromRevisionsTest extends TestCase
{
    private Curation $curation;

    public function setup(): void
    {
        parent::setup();
        Carbon::setTestNow('2020-01-01');
        $this->curation = factory(Curation::class)->create();

        // Start from a known slate: creating the curation revisions its own
        // attributes, which would otherwise be mistaken for status transitions.
        DB::table('revisions')
            ->where('revisionable_type', Curation::class)
            ->where('revisionable_id', $this->curation->id)
            ->delete();
    }

    /**
     * The shape this command exists for: a status a person really set, whose
     * history row is gone.
     *
     * @test
     */
    public function recovers_a_status_row_the_history_lost()
    {
        $published = config('curations.statuses.published');
        $this->loseHistoryFor($published, '2023-07-23');

        $this->artisan('curations:backfill-status-history-from-revisions', [
            'curation' => $this->curation->id,
        ])->assertSuccessful();

        $this->assertDatabaseHas('curation_curation_status', [
            'curation_id' => $this->curation->id,
            'curation_status_id' => $published,
            // The revision's own timestamp carries through, so the recovered row
            // records when the status actually changed, not just the day.
            'status_date' => '2023-07-23 09:00:00',
            'source' => 'revision-backfill',
        ]);

        $this->assertEquals(
            $published,
            $this->curation->fresh()->curation_status_id,
            'the status a person set must survive the repair'
        );
    }

    /**
     * @test
     */
    public function a_dry_run_writes_nothing()
    {
        $published = config('curations.statuses.published');
        $this->loseHistoryFor($published, '2023-07-23');

        $before = DB::table('curation_curation_status')->where('curation_id', $this->curation->id)->count();

        $this->artisan('curations:backfill-status-history-from-revisions', [
            'curation' => $this->curation->id,
            '--dry-run' => true,
        ])->assertSuccessful();

        $this->assertEquals(
            $before,
            DB::table('curation_curation_status')->where('curation_id', $this->curation->id)->count()
        );
    }

    /**
     * Deliberately narrow: a curation whose history already accounts for its status
     * is left alone, rather than gaining rows dated when the pointer was written.
     *
     * @test
     */
    public function leaves_a_curation_whose_history_already_agrees()
    {
        DB::table('revisions')->insert([
            'revisionable_type' => Curation::class,
            'revisionable_id' => $this->curation->id,
            'key' => 'curation_status_id',
            'old_value' => null,
            'new_value' => config('curations.statuses.published'),
            'created_at' => '2023-07-23 09:00:00',
            'updated_at' => '2023-07-23 09:00:00',
        ]);

        $before = DB::table('curation_curation_status')->where('curation_id', $this->curation->id)->count();

        $this->artisan('curations:backfill-status-history-from-revisions')
            ->expectsOutputToContain('No curations have a stored status their history cannot account for.')
            ->assertSuccessful();

        $this->assertEquals(
            $before,
            DB::table('curation_curation_status')->where('curation_id', $this->curation->id)->count()
        );
    }

    /**
     * @test
     */
    public function reports_a_curation_it_cannot_repair()
    {
        DB::table('curations')->where('id', $this->curation->id)
            ->update(['curation_status_id' => config('curations.statuses.published')]);

        $this->artisan('curations:backfill-status-history-from-revisions', [
            'curation' => $this->curation->id,
        ])
            ->expectsOutputToContain('cannot be repaired from revisions')
            ->assertSuccessful();
    }

    /**
     * Sets the pointer without the history row that should accompany it, and
     * records the revision that a real status change would have left behind.
     */
    private function loseHistoryFor(int $statusId, string $date): void
    {
        DB::table('curations')->where('id', $this->curation->id)
            ->update(['curation_status_id' => $statusId]);

        DB::table('revisions')->insert([
            'revisionable_type' => Curation::class,
            'revisionable_id' => $this->curation->id,
            'key' => 'curation_status_id',
            'old_value' => config('curations.statuses.uploaded'),
            'new_value' => $statusId,
            'user_id' => null,
            'created_at' => $date.' 09:00:00',
            'updated_at' => $date.' 09:00:00',
        ]);
    }
}
