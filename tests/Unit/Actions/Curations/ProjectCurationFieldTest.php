<?php

namespace Tests\Unit\Actions\Curations;

use App\Actions\Curations\ProjectCurationField;
use App\Actions\Curations\RecordCurationFieldEvent;
use App\Curation;
use App\CurationStatus;
use App\Curations\CurationField;
use App\Events\Curation\Updated;
use App\ExpertPanel;
use Carbon\Carbon;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * @group curations
 * @group curation-history
 */
class ProjectCurationFieldTest extends TestCase
{
    private Curation $curation;

    public function setup(): void
    {
        parent::setup();
        Carbon::setTestNow('2020-01-01 12:34:56');
        $this->curation = factory(Curation::class)->create();
    }

    /**
     * A curation can return to a panel that owned it before. The old code closed
     * every row for that panel, so the first tenure and the third became the same
     * interval.
     *
     * @test
     */
    public function derives_contiguous_intervals_including_a_return_to_a_previous_owner()
    {
        [$a, $b] = factory(ExpertPanel::class, 2)->create();

        $this->setOwner($a, '2021-01-01', 'one');
        $this->setOwner($b, '2021-06-01', 'two');
        $this->setOwner($a, '2022-01-01', 'three');

        // The first row is the ownership the curation was created with.
        $rows = $this->ownershipRows();

        $this->assertCount(4, $rows);
        $this->assertEquals('2021-01-01 00:00:00', $rows->first()->end_date);
        $this->assertEquals(
            [
                [$a->id, '2021-01-01 00:00:00', '2021-06-01 00:00:00'],
                [$b->id, '2021-06-01 00:00:00', '2022-01-01 00:00:00'],
                [$a->id, '2022-01-01 00:00:00', null],
            ],
            $this->intervalsAfterCreation()
        );
    }

    /**
     * Only the last tenure is open. The old SetOwner parsed a null end date into
     * "now", so no interval was ever actually left open.
     *
     * @test
     */
    public function leaves_only_the_current_owner_open_ended()
    {
        [$a, $b] = factory(ExpertPanel::class, 2)->create();

        $this->setOwner($a, '2021-01-01', 'one');
        $this->setOwner($b, '2021-06-01', 'two');

        $open = $this->ownershipRows()->whereNull('end_date');

        $this->assertCount(1, $open);
        $this->assertEquals($b->id, (int) $open->first()->expert_panel_id);
    }

    /**
     * @test
     */
    public function re_derives_surrounding_intervals_when_an_owner_is_inserted_mid_timeline()
    {
        [$a, $b, $c] = factory(ExpertPanel::class, 3)->create();

        $this->setOwner($a, '2021-01-01', 'one');
        $this->setOwner($b, '2022-01-01', 'two');
        $this->setOwner($c, '2021-06-01', 'backdated');

        $this->assertEquals(
            [
                [$a->id, '2021-01-01 00:00:00', '2021-06-01 00:00:00'],
                [$c->id, '2021-06-01 00:00:00', '2022-01-01 00:00:00'],
                [$b->id, '2022-01-01 00:00:00', null],
            ],
            $this->intervalsAfterCreation()
        );
    }

    /**
     * This is the property the whole replay story rests on. If projecting an
     * unchanged value wrote to the model, every replay would fire Curation\Updated
     * and produce an outgoing stream message.
     *
     * @test
     */
    public function projecting_an_unchanged_value_does_not_touch_the_curation()
    {
        $status = CurationStatus::find(config('curations.statuses.curation-provisional'));

        RecordCurationFieldEvent::run(
            $this->curation,
            CurationField::Status,
            $status->id,
            '2021-01-01',
            'gci',
            'key-one'
        );

        Event::fake([Updated::class]);

        ProjectCurationField::run($this->curation->fresh(), CurationField::Status);
        ProjectCurationField::run($this->curation->fresh(), CurationField::Status);

        Event::assertNotDispatched(Updated::class);
        $this->assertEquals($status->id, $this->curation->fresh()->curation_status_id);
    }

    /**
     * Derived data is rebuildable from history, which is what replaces the
     * per-symptom repair commands.
     *
     * @test
     */
    public function rebuilds_a_current_value_that_has_drifted_from_history()
    {
        $status = CurationStatus::find(config('curations.statuses.curation-approved'));

        RecordCurationFieldEvent::run(
            $this->curation,
            CurationField::Status,
            $status->id,
            '2021-01-01',
            'gci',
            'key-one'
        );

        \DB::table('curations')->where('id', $this->curation->id)->update([
            'curation_status_id' => config('curations.statuses.uploaded'),
        ]);

        ProjectCurationField::run($this->curation->fresh(), CurationField::Status);

        $this->assertEquals($status->id, $this->curation->fresh()->curation_status_id);
    }

    private function setOwner(ExpertPanel $panel, string $date, string $key): void
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

    /**
     * Ownership intervals excluding the row created with the curation itself.
     */
    private function intervalsAfterCreation(): array
    {
        return $this->ownershipRows()
            ->slice(1)
            ->map(fn ($r) => [(int) $r->expert_panel_id, $r->start_date, $r->end_date])
            ->values()
            ->all();
    }

    private function ownershipRows()
    {
        return \DB::table('curation_expert_panel')
            ->where('curation_id', $this->curation->id)
            ->orderBy('start_date')
            ->orderBy('id')
            ->get();
    }
}
