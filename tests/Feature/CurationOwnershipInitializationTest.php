<?php
namespace Tests\Feature;

use App\{Curation, ExpertPanel, User};
use App\Jobs\Curations\SetOwner;
use App\Mail\Curations\TransferNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

#[\PHPUnit\Framework\Attributes\Group('curation-ownership')]
class CurationOwnershipInitializationTest extends TestCase
{
    public function test_initialization_replay_never_adds_history_or_sends_transfer_mail(): void
    {
        Mail::fake();
        $panel = factory(ExpertPanel::class)->create();
        $coordinator = factory(User::class)->create();
        $panel->users()->attach($coordinator->id, ['is_coordinator' => true]);
        $c = factory(Curation::class)->create(['expert_panel_id' => $panel->id]);
        $before = DB::table('curation_expert_panel')->where('curation_id', $c->id)->get()->toArray();
        $this->assertCount(1, $before);
        SetOwner::initialize($c);
        SetOwner::initialize($c->fresh());
        SetOwner::dispatchSync($c, $panel->id, $c->created_at);
        $this->assertEquals($before, DB::table('curation_expert_panel')->where('curation_id', $c->id)->get()->toArray());
        Mail::assertNotSent(TransferNotification::class);
    }

    public function test_real_transfer_closes_old_owner_and_initialization_replay_does_not_reopen_it(): void
    {
        Mail::fake();
        $a = factory(ExpertPanel::class)->create(); $b = factory(ExpertPanel::class)->create();
        $coordinator = factory(User::class)->create();
        $b->users()->attach($coordinator->id, ['is_coordinator' => true]);
        $c = factory(Curation::class)->create(['expert_panel_id' => $a->id, 'created_at' => '2026-01-01 12:00:00']);
        $stale = $c->fresh();
        SetOwner::dispatchSync($c, $b->id, '2026-02-01', '2026-02-01');
        $history = $c->expertPanels()->get();
        $this->assertCount(2, $history);
        $this->assertSame('2026-02-01', $history->firstWhere('id', $a->id)->pivot->end_date->toDateString());
        $this->assertNull($history->firstWhere('id', $b->id)->pivot->end_date);
        $this->assertSame('2026-02-01', $history->firstWhere('id', $b->id)->pivot->start_date->toDateString());
        $this->assertEquals($b->id, $c->fresh()->expert_panel_id);
        SetOwner::initialize($stale);
        SetOwner::initialize($c->fresh());
        SetOwner::dispatchSync($stale, $b->id, '2026-02-01');
        $this->assertCount(2, $c->fresh()->expertPanels()->get());
        Mail::assertSent(TransferNotification::class, 1);
    }

    public function test_transfer_repairs_missing_previous_history_for_existing_curation(): void
    {
        Mail::fake();
        $a = factory(ExpertPanel::class)->create(); $b = factory(ExpertPanel::class)->create();
        $c = factory(Curation::class)->create(['expert_panel_id' => $a->id, 'created_at' => '2026-01-01 12:00:00']);
        // Disposable test fixture simulating legacy data, never production cleanup.
        $c->expertPanels()->detach();
        SetOwner::dispatchSync($c, $b->id, '2026-02-01', '2026-02-01');
        $history = $c->expertPanels()->get();
        $this->assertCount(2, $history);
        $this->assertSame('2026-01-01', $history->firstWhere('id', $a->id)->pivot->start_date->toDateString());
        $this->assertSame('2026-02-01', $history->firstWhere('id', $a->id)->pivot->end_date->toDateString());
        $this->assertNull($history->firstWhere('id', $b->id)->pivot->end_date);
    }
}
