<?php

namespace Tests\Feature\Curations;

use App\Actions\Curations\RecordCurationFieldEvent;
use App\Curation;
use App\Curations\CurationField;
use Carbon\Carbon;
use Tests\TestCase;

/**
 * @group curations
 * @group curation-history
 */
class AuditStatusTransitionsTest extends TestCase
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
    public function reports_a_lawful_sequence_as_legal()
    {
        $this->record(config('curations.statuses.precuration'), '2020-02-01');

        $this->artisan('curations:audit-status-transitions', ['curation' => $this->curation->id])
            ->expectsOutputToContain('start')
            ->expectsOutputToContain('legal')
            ->doesntExpectOutputToContain('not in graph')
            ->assertSuccessful();
    }

    /**
     * @test
     */
    public function flags_a_transition_outside_the_graph()
    {
        // Uploaded -> Published is a jump the state machine does not allow, and is
        // what a curation looks like when the steps between were never recorded.
        $this->record(config('curations.statuses.published'), '2020-02-01');

        $this->artisan('curations:audit-status-transitions', ['curation' => $this->curation->id])
            ->expectsOutputToContain('not in graph')
            ->assertSuccessful();
    }

    /**
     * @test
     */
    public function summarises_the_whole_database()
    {
        $this->record(config('curations.statuses.published'), '2020-02-01');

        $this->artisan('curations:audit-status-transitions', ['--limit' => 5])
            ->expectsOutputToContain('transition(s), sequenced by rank order')
            ->expectsOutputToContain('First recorded status')
            ->expectsOutputToContain('no Uploaded row at all')
            ->assertSuccessful();
    }

    /**
     * @test
     */
    public function rejects_an_unknown_ordering()
    {
        $this->artisan('curations:audit-status-transitions', ['--ordering' => 'nonsense'])
            ->assertFailed();
    }

    /**
     * @test
     */
    public function reports_a_missing_curation()
    {
        $this->artisan('curations:audit-status-transitions', ['curation' => '99999999'])
            ->assertFailed();
    }

    private function record(int $statusId, string $date): void
    {
        RecordCurationFieldEvent::run(
            $this->curation,
            CurationField::Status,
            $statusId,
            $date,
            'test',
            'test:'.$statusId.':'.$date
        );
    }
}
