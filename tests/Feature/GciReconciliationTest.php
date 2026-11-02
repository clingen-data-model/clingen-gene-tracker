<?php
namespace Tests\Feature;

use App\{Affiliation, Curation, Disease, ExpertPanel, Gene, ModeOfInheritance, User};
use App\Services\GciReconciliation\{Decision, Comparison, SafeUpdates, PrecurationExport, Review, PayloadReader, Reconciler, Record};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class GciReconciliationTest extends TestCase
{
    private array $paths = [];
    private $actor;
    private $panel;
    private $gene;
    private $disease;
    private $moi;
    protected $fakeCurationSavedEvent = false;

    public function setUp(): void
    {
        parent::setUp();
        $this->actor = factory(User::class)->create();
        $parent = factory(Affiliation::class)->create(['clingen_id' => 998811]);
        $child = factory(Affiliation::class)->create(['parent_id' => $parent->id, 'clingen_id' => 998812]);
        $this->panel = factory(ExpertPanel::class)->create(['affiliation_id' => $child->id]);
        $this->gene = factory(Gene::class)->create(['gene_symbol' => 'GCI_TEST_GENE']);
        $this->disease = factory(Disease::class)->create(['mondo_id' => 'MONDO:9988110', 'is_obsolete' => false]);
        $this->moi = ModeOfInheritance::where('hp_id', 'HP:0000006')->firstOrFail();
    }
    protected function tearDown(): void
    {
        foreach ($this->paths as $path) if (is_file($path)) unlink($path);
        parent::tearDown();
    }
    private function item(array $extra = []): array
    {
        return array_merge(['gene' => $this->gene->gene_symbol, 'PK' => (string) Str::uuid(),
            'affiliation' => '998811', 'modeInheritance' => 'Autosomal dominant inheritance (HP:0000006)',
            'disease' => 'MONDO_9988110'], $extra);
    }
    private function curation(array $extra = []): Curation
    {
        return factory(Curation::class)->create(array_merge(['gene_symbol' => $this->gene->gene_symbol,
            'expert_panel_id' => $this->panel->id, 'gdm_uuid' => (string) Str::uuid(), 'mondo_id' => $this->disease->mondo_id, 'moi_id' => $this->moi->id], $extra));
    }
    private function reconcile(array $has, array $no): \Illuminate\Database\Eloquent\Collection
    {
        $paths = [];
        foreach ([$has, $no] as $items) {
            $path = tempnam(sys_get_temp_dir(), 'gci-test-');
            file_put_contents($path, json_encode(['Items' => $items]));
            $paths[] = $path; $this->paths[] = $path;
        }
        $uuid = (string) Str::uuid();
        app(Reconciler::class)->reconcile($uuid, 'test', ...$paths);
        return Record::where('run_uuid', $uuid)->orderBy('id')->get();
    }
    public function test_matching_normalization_fallback_deleted_retired_and_candidates(): void
    {
        $direct = $this->curation(); $fallback = $this->curation(); $retired = $this->curation(['curation_status_id' => config('curations.statuses.retired-assignment')]);
        // Set after creation because the model assigns its default status at creation.
        DB::table('curations')->where('id', $retired->id)->update(['curation_status_id' => config('curations.statuses.retired-assignment')]);
        $deleted = $this->curation(); $deleted->delete();
        $uuid = (string) Str::uuid(); $this->curation(['gdm_uuid' => $uuid]); $this->curation(['gdm_uuid' => $uuid]);
        $rows = $this->reconcile([
            $this->item(['precurationId' => (string) $direct->id, 'PK' => $direct->gdm_uuid]),
            $this->item(['precurationId' => '4294967294', 'PK' => $fallback->gdm_uuid]),
        ], [$this->item(['PK' => $retired->gdm_uuid]), $this->item(['PK' => $deleted->gdm_uuid]), $this->item(['PK' => $uuid]), $this->item()]);
        $this->assertSame('PRECURATION_ID', $rows[0]->match_method);
        $this->assertSame('MONDO:9988110', $rows[0]->gci_mondo_id);
        $this->assertSame('HP:0000006', $rows[0]->gci_moi_hp_id);
        $this->assertSame('GDM_UUID', $rows[1]->match_method);
        $this->assertTrue($rows[1]->has_identity_warning);
        $this->assertTrue($rows[2]->gt_is_retired);
        $this->assertSame('NOT_FOUND_IN_GT', $rows[3]->match_status);
        $this->assertContains($deleted->id, $rows[3]->deleted_gt_curation_ids);
        $this->assertSame('AMBIGUOUS_GDM_UUID', $rows[4]->match_status);
        $this->assertNull($rows[5]->gt_curation_id);
        $this->assertNotEmpty(app(Comparison::class)->candidates($rows[5]));
        $this->assertFalse(Record::filtered(['run_uuid' => $rows[2]->run_uuid])->whereKey($rows[2]->id)->exists());
        $this->assertTrue(Record::filtered(['include_retired' => true])->whereKey($rows[2]->id)->exists());
    }
    public function test_safe_commands_dry_run_apply_history_and_immutable_snapshots(): void
    {
        foreach (['has', 'no'] as $source) foreach (['disease', 'moi', 'both'] as $case) {
            $c = $this->curation(['mondo_id' => $case === 'moi' ? $this->disease->mondo_id : null,
                'moi_id' => $case === 'disease' ? $this->moi->id : null]);
            $item = $this->item(['PK' => $c->gdm_uuid]);
            if ($source === 'has') $item['precurationId'] = (string) $c->id;
            $r = $this->reconcile($source === 'has' ? [$item] : [], $source === 'no' ? [$item] : [])->first();
            $snapshot = $r->only(['raw_payload', 'gt_mondo_id', 'gt_moi_hp_id', 'disease_match', 'moi_match', 'issue_codes']);
            $before = $c->fresh()->getAttributes();
            $messages = DB::table('stream_messages')->count();
            $this->artisan('gci:apply-safe-updates', ['--id' => [$r->id]])->assertExitCode(0);
            $this->assertEquals($before, $c->fresh()->getAttributes());
            $this->assertSame('NOT_READY', $r->fresh()->execution_status);
            $this->assertSame($messages, DB::table('stream_messages')->count());
            $this->artisan('gci:apply-safe-updates', ['--apply' => true, '--id' => [$r->id], '--user' => $this->actor->id])->assertExitCode(0);
            $this->assertSame('APPLIED', $r->fresh()->execution_status);
            $this->assertEquals($this->actor->id, $r->fresh()->executed_by);
            $this->assertSame($this->disease->mondo_id, $c->fresh()->mondo_id);
            $this->assertSame($this->moi->id, $c->fresh()->moi_id);
            foreach (['gene_symbol', 'gdm_uuid', 'affiliation_id', 'expert_panel_id'] as $field) $this->assertEquals($before[$field], $c->fresh()->$field);
            $this->assertSame($snapshot, $r->fresh()->only(array_keys($snapshot)));
            $this->assertGreaterThan($messages, DB::table('stream_messages')->count());
            $this->assertDatabaseHas('revisions', ['revisionable_id' => $c->id, 'key' => $case === 'moi' ? 'moi_id' : 'mondo_id', 'user_id' => $this->actor->id]);
            $this->assertTrue(app(Comparison::class)->describe($r)['current_matches_gci']);
            $this->assertSame('SKIPPED', app(SafeUpdates::class)->apply($r->id, null)['status']);
        }
    }
    public function test_identity_mismatches_and_live_changes_block_updates(): void
    {
        foreach (['gene', 'affiliation', 'uuid', 'duplicate', 'live'] as $problem) {
            $c = $this->curation(['mondo_id' => null]);
            $item = $this->item(['PK' => $c->gdm_uuid, 'precurationId' => (string) $c->id]);
            if ($problem === 'gene') $item['gene'] = 'OTHER';
            if ($problem === 'affiliation') $item['affiliation'] = '999999';
            if ($problem === 'uuid') $item['PK'] = (string) Str::uuid();
            if ($problem === 'duplicate') $this->curation(['gdm_uuid' => $c->gdm_uuid]);
            $r = $this->reconcile([$item], [])->first();
            if ($problem === 'live') $c->update(['gdm_uuid' => (string) Str::uuid()]);
            $inspection = app(SafeUpdates::class)->inspect([$r]);
            $this->assertEmpty($inspection['plans'], $problem);
            $this->assertNotEmpty($inspection['excluded']);
            $this->assertNull($c->fresh()->mondo_id);
        }
    }
    public function test_missing_mappings_fail_safely_and_other_rows_continue(): void
    {
        $has = [];
        foreach (['disease', 'moi', 'valid'] as $case) {
            $c = $this->curation(['mondo_id' => null, 'moi_id' => null]);
            $item = $this->item(['PK' => $c->gdm_uuid, 'precurationId' => (string) $c->id]);
            if ($case === 'disease') $item['disease'] = 'MONDO_9999999';
            if ($case === 'moi') $item['modeInheritance'] = 'Unknown (HP:9999999)';
            $has[] = $item;
        }
        $rows = $this->reconcile($has, []);
        $this->artisan('gci:apply-safe-updates', ['--apply' => true, '--run' => $rows[0]->run_uuid])->assertExitCode(1);
        $this->assertSame('FAILED', $rows[0]->fresh()->execution_status);
        $this->assertSame('FAILED', $rows[1]->fresh()->execution_status);
        $this->assertSame('APPLIED', $rows[2]->fresh()->execution_status);
        $this->assertNull(Curation::find($rows[0]->gt_curation_id)->mondo_id);
    }
    public function test_live_comparison_and_search_leave_snapshots_unchanged(): void
    {
        $c = $this->curation(['mondo_id' => null]);
        $r = $this->reconcile([$this->item(['precurationId' => (string) $c->id, 'PK' => $c->gdm_uuid])], [])->first();
        $snapshot = $r->getAttributes();
        $this->assertFalse(app(Comparison::class)->describe($r)['current_matches_gci']);
        app(\App\Services\Curations\UpdateCuration::class)->update($c, ['mondo_id' => $this->disease->mondo_id]);
        $this->assertTrue(app(Comparison::class)->describe($r)['current_matches_gci']);
        $this->assertSame($snapshot, $r->fresh()->getAttributes());
        foreach (['gci_precuration_id', 'gt_curation_id', 'resolved_gt_curation_id'] as $field) {
            $r->update([$field => 4294967290]);
            $this->assertTrue(Record::filtered(['curation_id' => 4294967290])->whereKey($r->id)->exists());
            $r->update([$field => $snapshot[$field]]);
        }
        foreach (['gci_gdm_uuid', 'gt_gdm_uuid'] as $field) {
            $uuid = (string) Str::uuid(); $r->update([$field => $uuid]);
            $this->assertTrue(Record::filtered(['gdm_uuid' => $uuid])->whereKey($r->id)->exists());
        }
    }
    public function test_export_preserves_raw_values_and_includes_reviewed_manual_targets(): void
    {
        $c = $this->curation(); $manual = $this->curation(['gdm_uuid' => null]);
        $items = [$this->item(['PK' => $c->gdm_uuid]), $this->item(), $this->item()];
        $rows = $this->reconcile([], $items);
        app(Review::class)->save([$rows[1]->id], ['review_status' => 'REVIEWED', 'resolution' => Decision::MATCH,
            'resolved_gt_curation_id' => $manual->id, 'review_notes' => 'Confirmed manually'], $this->actor->id);
        $result = app(PrecurationExport::class)->payload($rows->fresh());
        $this->assertSame(2, $result['payload']['Count']);
        $this->assertEquals($items[0] + ['precurationId' => (string) $c->id], $result['payload']['Items'][0]);
        $this->assertSame((string) $manual->id, $result['payload']['Items'][1]['precurationId']);
        $this->assertSame(Decision::EXPORT, app(Comparison::class)->describe($rows[0])['gci_action']);
        $path = sys_get_temp_dir().'/gci-export-'.Str::uuid().'.json'; $this->paths[] = $path;
        $this->artisan('gci:export-precuration-ids', ['--run' => $rows[0]->run_uuid, '--output' => $path])->assertExitCode(0);
        $this->assertSame(2, json_decode(file_get_contents($path), true)['Count']);
        $this->assertNotNull($rows[0]->fresh()->exported_at);
        $this->assertNull($rows[2]->fresh()->exported_at);
    }
    public function test_admin_authorization_review_and_read_only_routes(): void
    {
        $c = $this->curation(); $r = $this->reconcile([], [$this->item(['PK' => $c->gdm_uuid])])->first();
        $this->actingAs($this->actor, 'api')->getJson('/api/admin/gci-reconciliation/records')->assertForbidden();
        $this->actor->assignRole('admin');
        foreach (['list', 'review'] as $action) $this->actor->givePermissionTo(\Spatie\Permission\Models\Permission::findOrCreate($action.' gci-reconciliation', 'web'));
        $this->actor->unsetRelation('permissions');
        $this->getJson('/api/admin/gci-reconciliation/records?curation_id='.$c->id)->assertOk()->assertJsonFragment(['gt_curation_id' => $c->id]);
        $this->getJson('/api/admin/gci-reconciliation/records/'.$r->id)->assertOk()->assertJsonPath('record.current_matches_gci', true);
        $before = $c->fresh()->getAttributes();
        $this->postJson('/api/admin/gci-reconciliation/review', ['ids' => [$r->id], 'review_status' => 'REVIEWED',
            'resolution' => Decision::AFFILIATION, 'review_notes' => 'Manual follow-up'])->assertNoContent();
        $this->assertEquals($before, $c->fresh()->getAttributes());
        foreach (['runs', 'uploads', 'preview', 'execute', 'export'] as $endpoint) $this->postJson('/api/admin/gci-reconciliation/'.$endpoint)->assertNotFound();
    }
    public function test_failed_normal_update_rolls_back_gt_history_and_records_failure(): void
    {
        $c = $this->curation(['mondo_id' => null]);
        $r = $this->reconcile([$this->item(['precurationId' => (string) $c->id, 'PK' => $c->gdm_uuid])], [])->first();
        $messages = DB::table('stream_messages')->count();
        $revisions = DB::table('revisions')->count();
        $this->instance(\App\Services\Curations\UpdateCuration::class, new class extends \App\Services\Curations\UpdateCuration {
            public function update(Curation $curation, array $attributes): Curation {
                parent::update($curation, $attributes);
                throw new \RuntimeException('Simulated application failure');
            }
        });
        $this->assertSame('FAILED', app(SafeUpdates::class)->apply($r->id, null)['status']);
        $this->assertNull($c->fresh()->mondo_id);
        $this->assertSame($messages, DB::table('stream_messages')->count());
        $this->assertSame($revisions, DB::table('revisions')->count());
        $this->assertSame('Simulated application failure', $r->fresh()->execution_error);
    }
    public function test_export_excludes_duplicate_uuid_and_unresolved_manual_decisions(): void
    {
        $c = $this->curation();
        $r = $this->reconcile([], [$this->item(['PK' => $c->gdm_uuid])])->first();
        $r->update(['resolution' => Decision::AFFILIATION]);
        $this->assertSame(0, app(PrecurationExport::class)->payload([$r])['payload']['Count']);
        $r->update(['resolution' => null]);
        $duplicate = $this->curation(['gdm_uuid' => $c->gdm_uuid]);
        $duplicate->delete();
        $this->assertSame(0, app(PrecurationExport::class)->payload([$r])['payload']['Count']);
    }
    public function test_multiple_runs_require_an_explicit_selection(): void
    {
        $r = $this->reconcile([], [$this->item()])->first();
        $this->reconcile([], [$this->item()]);
        $this->artisan('gci:apply-safe-updates')->assertExitCode(1);
        $this->artisan('gci:apply-safe-updates', ['--run' => $r->run_uuid])->assertExitCode(0);
    }

    public function test_final_migration_creates_retained_schema_without_workflow_tables(): void
    {
        $original = DB::getDefaultConnection();
        config(['database.connections.gci_schema_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
        try {
            DB::setDefaultConnection('gci_schema_test');
            require_once database_path('migrations/2026_07_29_000000_create_gci_gt_reconciliations_table.php');
            (new \CreateGciGtReconciliationsTable)->up();
            $schema = DB::connection()->getSchemaBuilder();
            $this->assertTrue($schema->hasColumns('gci_gt_reconciliations', ['raw_payload', 'gt_mondo_id', 'gt_is_retired',
                'deleted_gt_curation_ids', 'resolved_gt_curation_id', 'reviewed_by', 'reviewed_at', 'execution_status',
                'executed_by', 'executed_at', 'execution_error', 'exported_at']));
            foreach (['runs', 'uploads', 'audits', 'previews'] as $suffix) $this->assertFalse($schema->hasTable('gci_reconciliation_'.$suffix));
        } finally {
            DB::setDefaultConnection($original);
            DB::purge('gci_schema_test');
        }
    }

    public function test_review_records_manual_resolution_from_live_gt_without_changing_snapshots_or_gt(): void
    {
        $c = $this->curation(['mondo_id' => null]);
        $r = $this->reconcile([$this->item(['PK' => $c->gdm_uuid, 'precurationId' => (string) $c->id])], [])->first();
        $snapshot = $r->getAttributes();
        $review = app(Review::class);
        $review->save([$r->id], ['review_status' => 'REVIEWED'], $this->actor->id);
        $this->assertSame('NOT_READY', $r->fresh()->execution_status);
        app(\App\Services\Curations\UpdateCuration::class)->update($c, ['mondo_id' => $this->disease->mondo_id]);
        $review->save([$r->id], ['review_status' => 'PENDING'], $this->actor->id);
        $this->assertSame('NOT_READY', $r->fresh()->execution_status);
        $beforeGt = $c->fresh()->getAttributes();
        $revisions = DB::table('revisions')->count();
        $messages = DB::table('stream_messages')->count();
        $review->save([$r->id], ['review_status' => 'REVIEWED'], $this->actor->id);
        $this->assertSame('RESOLVED_MANUALLY', $r->fresh()->execution_status);
        $this->assertEquals($beforeGt, $c->fresh()->getAttributes());
        $this->assertSame($revisions, DB::table('revisions')->count());
        $this->assertSame($messages, DB::table('stream_messages')->count());
        foreach (['review_status', 'reviewed_by', 'reviewed_at', 'execution_status', 'updated_at'] as $key) unset($snapshot[$key]);
        $this->assertSame($snapshot, array_intersect_key($r->fresh()->getAttributes(), $snapshot));
        $c->update(['mondo_id' => null]);
        $review->save([$r->id], ['review_status' => 'REVIEWED'], $this->actor->id);
        $this->assertSame('NOT_READY', $r->fresh()->execution_status);
    }
    public function test_review_preserves_completed_actions_and_marks_already_matching_rows_no_action(): void
    {
        $rows = [];
        foreach (['APPLIED', 'NO_ACTION', 'NOT_READY'] as $status) {
            $c = $this->curation();
            $r = $this->reconcile([], [$this->item(['PK' => $c->gdm_uuid])])->first();
            $r->update(['execution_status' => $status]);
            $rows[] = $r;
        }
        app(Review::class)->save(array_map(fn ($r) => $r->id, $rows), ['review_status' => 'REVIEWED'], $this->actor->id);
        $this->assertSame('APPLIED', $rows[0]->fresh()->execution_status);
        $this->assertSame('NO_ACTION', $rows[1]->fresh()->execution_status);
        $this->assertSame('NO_ACTION', $rows[2]->fresh()->execution_status);
        $this->assertTrue(Record::filtered(['queue' => 'export'])->whereKey($rows[2]->id)->exists());
        $this->assertSame(1, app(PrecurationExport::class)->payload([$rows[2]->fresh()])['payload']['Count']);
    }

}
