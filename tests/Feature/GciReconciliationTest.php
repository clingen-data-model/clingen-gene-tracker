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
        $exportSnapshot = $rows->fresh()->map->getAttributes()->all();
        $this->artisan('gci:export-precuration-ids', ['--run' => $rows[0]->run_uuid, '--output' => $path])->assertExitCode(0);
        $this->assertSame($exportSnapshot, $rows->fresh()->map->getAttributes()->all());
        $this->assertSame(2, json_decode(file_get_contents($path), true)['Count']);
        $this->assertNull($rows[0]->fresh()->exported_at);
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
        $review->save([$r->id], ['review_status' => 'PENDING'], $this->actor->id);
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
        $c->update(['gdm_uuid' => (string) Str::uuid()]);
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

    public function test_gci_action_reviews_exports_and_filters_preserve_gt_and_existing_decisions(): void
    {
        $this->actor->assignRole('admin');
        foreach (['list', 'review'] as $action) $this->actor->givePermissionTo(\Spatie\Permission\Models\Permission::findOrCreate($action.' gci-reconciliation', 'web'));
        $this->actingAs($this->actor, 'api');
        $c = $this->curation();
        $rows = $this->reconcile([], [$this->item(['PK' => $c->gdm_uuid]), $this->item(), $this->item(), $this->item()]);
        $beforeGt = $c->fresh()->getAttributes();
        $revisions = DB::table('revisions')->count();
        foreach ([Decision::DELETE_GCI, Decision::PARKING_LOT, 'KEEP_DELETED'] as $index => $resolution) {
            $this->postJson('/api/admin/gci-reconciliation/review', ['ids' => [$rows[$index]->id],
                'review_status' => 'REVIEWED', 'resolution' => $resolution])->assertNoContent();
            $this->getJson('/api/admin/gci-reconciliation/records?resolution='.$resolution)
                ->assertOk()->assertJsonFragment(['id' => $rows[$index]->id, 'resolution' => $resolution]);
        }
        $rows[3]->update(['resolution' => Decision::DELETE_GCI]); // Pending is not an approved GCI action.
        $this->assertSame($beforeGt, $c->fresh()->getAttributes());
        $this->assertSame($revisions, DB::table('revisions')->count());
        foreach ([0, 1] as $index) {
            $r = $rows[$index]->fresh();
            $this->assertNotSame('APPLIED', $r->execution_status);
            $this->assertSame($r->resolution, app(Comparison::class)->describe($r)['gci_action']);
            $this->assertFalse(Record::filtered(['queue' => 'export'])->whereKey($r->id)->exists());
            $this->assertFalse(Record::filtered(['queue' => 'safe'])->whereKey($r->id)->exists());
        }
        $this->assertSame(0, app(PrecurationExport::class)->payload($rows->fresh())['payload']['Count']);
        $expected = ['Items' => [
            ['PK' => $rows[0]->gci_gdm_uuid, 'resolution' => Decision::DELETE_GCI],
            ['PK' => $rows[1]->gci_gdm_uuid, 'resolution' => Decision::PARKING_LOT],
        ], 'Count' => 2];
        $this->assertSame($expected, app(\App\Services\GciReconciliation\ActionExport::class)->payload($rows->fresh())['payload']);
        $snapshot = $rows->fresh()->map->getAttributes()->all();
        foreach (['gci:export-actions' => $expected, 'gci:export-precuration-ids' => null] as $command => $payload) {
            $path = sys_get_temp_dir().'/gci-export-'.Str::uuid().'.json'; $this->paths[] = $path;
            $this->artisan($command, ['--run' => $rows[0]->run_uuid, '--output' => $path])->assertExitCode(0);
            $actual = json_decode(file_get_contents($path), true);
            if ($payload) $this->assertSame($payload, $actual); else $this->assertSame(0, $actual['Count']);
            $this->assertSame($snapshot, $rows->fresh()->map->getAttributes()->all());
        }
    }
    public function test_action_export_excludes_invalid_conflicting_and_other_outcomes(): void
    {
        $uuid = (string) Str::uuid();
        $rows = collect([
            new Record(['id' => 1, 'review_status' => 'REVIEWED', 'resolution' => Decision::DELETE_GCI, 'gci_gdm_uuid' => $uuid]),
            new Record(['id' => 2, 'review_status' => 'REVIEWED', 'resolution' => Decision::PARKING_LOT, 'gci_gdm_uuid' => $uuid]),
            new Record(['id' => 3, 'review_status' => 'REVIEWED', 'resolution' => Decision::DELETE_GCI, 'gci_gdm_uuid' => 'invalid']),
        ]);
        foreach ([Decision::EXPORT, Decision::UPDATE, 'NO_ACTION', 'KEEP_DELETED', null] as $resolution) {
            $rows->push(new Record(['review_status' => 'REVIEWED', 'resolution' => $resolution, 'gci_gdm_uuid' => (string) Str::uuid()]));
        }
        $result = app(\App\Services\GciReconciliation\ActionExport::class)->payload($rows);
        $this->assertSame(['Items' => [], 'Count' => 0], $result['payload']);
        $this->assertCount(8, $result['skipped']);
    }

    public function test_ui_downloads_share_cli_payloads_filters_and_do_not_mutate_rows(): void
    {
        $this->actor->assignRole('admin');
        $this->actor->givePermissionTo(\Spatie\Permission\Models\Permission::findOrCreate('list gci-reconciliation', 'web'));
        $this->actingAs($this->actor, 'api');
        $c = $this->curation();
        $items = [$this->item(['PK' => $c->gdm_uuid])];
        for ($n = 0; $n < 52; $n++) $items[] = $this->item();
        $rows = $this->reconcile([], $items);
        foreach ($rows->slice(1) as $r) $r->update(['review_status' => 'REVIEWED', 'resolution' => Decision::DELETE_GCI]);
        $rows[2]->update(['resolution' => Decision::PARKING_LOT]);
        $rows[3]->update(['review_status' => 'PENDING']);
        $before = $rows->fresh()->map->getAttributes()->all();
        $base = '/api/admin/gci-reconciliation/export/';
        foreach (['actions' => \App\Services\GciReconciliation\ActionExport::class, 'precuration-ids' => PrecurationExport::class] as $type => $service) {
            $response = $this->getJson($base.$type.'?scope=filtered&run_uuid='.$rows[0]->run_uuid.'&page=2');
            $response->assertOk()->assertHeader('Content-Type', 'application/json');
            $this->assertStringContainsString($type === 'actions' ? 'gci-actions-' : 'gci-precuration-id-updates-', $response->headers->get('Content-Disposition'));
            $this->assertSame(app($service)->payload($rows->fresh())['payload'], json_decode($response->streamedContent(), true));
        }
        $filtered = $this->getJson($base.'actions?'.http_build_query(['scope' => 'filtered', 'run_uuid' => $rows[0]->run_uuid, 'resolution' => Decision::DELETE_GCI]))->assertOk();
        $this->assertSame(50, json_decode($filtered->streamedContent(), true)['Count']);
        $all = $this->getJson($base.'actions?scope=all&source_type=HAS_PRECURATION_ID')->assertOk();
        $this->assertSame(app(\App\Services\GciReconciliation\ActionExport::class)->payload(Record::orderBy('id')->get())['payload'], json_decode($all->streamedContent(), true));
        foreach ([['source_type' => PayloadReader::HAS], ['resolution' => 'KEEP_DELETED']] as $filter) {
            $this->getJson($base.'actions?'.http_build_query($filter + ['scope' => 'filtered', 'run_uuid' => $rows[0]->run_uuid]))->assertUnprocessable()->assertJsonValidationErrors('export');
        }
        foreach ([['curation_id' => $c->id], ['gdm_uuid' => $c->gdm_uuid], ['source_type' => PayloadReader::NO]] as $filter) {
            $response = $this->getJson($base.'precuration-ids?'.http_build_query($filter + ['scope' => 'filtered', 'run_uuid' => $rows[0]->run_uuid]))->assertOk();
            $this->assertSame(1, json_decode($response->streamedContent(), true)['Count']);
        }
        $this->getJson($base.'actions?scope=filtered&gdm_uuid=invalid')->assertUnprocessable()->assertJsonValidationErrors('gdm_uuid');
        $this->assertSame($before, $rows->fresh()->map->getAttributes()->all());
        $other = factory(User::class)->create();
        $this->actingAs($other, 'api')->getJson($base.'actions?scope=all')->assertForbidden();
        $other->assignRole('admin');
        // An admin role without the list permission is also rejected.
        $role = \Spatie\Permission\Models\Role::findByName('admin', 'web');
        $role->revokePermissionTo('list gci-reconciliation');
        $other->unsetRelation('roles');
        $this->getJson($base.'precuration-ids?scope=all')->assertForbidden();
    }

    public function test_export_query_count_is_bounded_and_loaded_data_is_not_cached_across_calls(): void
    {
        $items = [];
        for ($n = 0; $n < 40; $n++) {
            $c = $this->curation();
            $items[] = $this->item(['PK' => $c->gdm_uuid]);
        }
        $rows = $this->reconcile([], $items);
        // Mix automatic and explicitly reviewed targets in the same bulk export.
        $rows[0]->fill(['review_status' => 'REVIEWED', 'resolution' => Decision::MATCH,
            'resolved_gt_curation_id' => $rows[0]->gt_curation_id]);
        DB::flushQueryLog(); DB::enableQueryLog();
        try {
            $result = app(PrecurationExport::class)->payload($rows);
            $queries = DB::getQueryLog();
        } finally { DB::disableQueryLog(); DB::flushQueryLog(); }
        $this->assertSame(40, $result['payload']['Count']);
        $this->assertLessThanOrEqual(5, count($queries), 'Export should bulk-load targets/relations and UUID owners, not query per row.');
        $this->assertEmpty($result['skipped']);
        $target = Curation::findOrFail($rows[0]->gt_curation_id);
        $target->update(['archived_at' => now()]);
        $this->assertSame(39, app(PrecurationExport::class)->payload($rows)['payload']['Count']);
        $rows[0]->resolved_gt_curation_id = 4294967294;
        DB::enableQueryLog();
        try {
            $missing = app(PrecurationExport::class)->payload([$rows[0]]);
            $queries = DB::getQueryLog();
        } finally { DB::disableQueryLog(); DB::flushQueryLog(); }
        $this->assertSame(0, $missing['payload']['Count']);
        $this->assertStringContainsString('missing', $missing['skipped'][0]['reason']);
        $this->assertLessThanOrEqual(2, count($queries), 'A missing bulk-loaded target must not fall back to a per-row lookup.');
    }

    public function test_bulk_export_preserves_manual_duplicate_owner_and_correction_rules(): void
    {
        foreach (['retired', 'archived', 'deleted', 'active'] as $state) {
            $c = $this->curation(); $other = $this->curation(['gdm_uuid' => $c->gdm_uuid]);
            $r = $this->reconcile([], [$this->item(['PK' => $c->gdm_uuid])])->first();
            if ($state === 'retired') DB::table('curations')->where('id', $other->id)->update(['curation_status_id' => config('curations.statuses.retired-assignment')]);
            if ($state === 'archived') $other->update(['archived_at' => now()]);
            if ($state === 'deleted') $other->delete();
            $r->fill(['review_status' => 'REVIEWED', 'resolution' => Decision::MATCH, 'resolved_gt_curation_id' => $c->id]);
            $result = app(PrecurationExport::class)->payload([$r]);
            $this->assertSame($state === 'active' ? 0 : 1, $result['payload']['Count'], $state);
            if ($state !== 'active') $this->assertSame((string) $c->id, $result['payload']['Items'][0]['precurationId']);
        }
        $target = $this->curation(); $wrong = $this->curation();
        $r = $this->reconcile([$this->item(['PK' => $target->gdm_uuid, 'precurationId' => (string) $wrong->id])], [])->first();
        $r->update(['review_status' => 'REVIEWED', 'resolution' => Decision::EXPORT, 'resolved_gt_curation_id' => $target->id]);
        $expected = app(PrecurationExport::class)->payload([$r])['payload'];
        $this->assertSame((string) $target->id, $expected['Items'][0]['precurationId']);
        $path = sys_get_temp_dir().'/gci-bulk-'.Str::uuid().'.json'; $this->paths[] = $path;
        $this->artisan('gci:export-precuration-ids', ['--id' => [$r->id], '--output' => $path])->assertExitCode(0);
        $this->assertSame($expected, json_decode(file_get_contents($path), true));
        $this->actor->assignRole('admin');
        $this->actor->givePermissionTo(\Spatie\Permission\Models\Permission::findOrCreate('list gci-reconciliation', 'web'));
        $response = $this->actingAs($this->actor, 'api')->getJson('/api/admin/gci-reconciliation/export/precuration-ids?scope=filtered&run_uuid='.$r->run_uuid)->assertOk();
        $this->assertSame($expected, json_decode($response->streamedContent(), true));
        $this->assertEquals($wrong->id, $r->fresh()->gci_precuration_id);
    }

    public function test_review_association_keeps_one_initial_ownership_record(): void
    {
        $c = $this->curation();
        $r = $this->reconcile([], [$this->item(['PK' => $c->gdm_uuid])])->first();
        $before = DB::table('curation_expert_panel')->where('curation_id', $c->id)->get()->toArray();
        $this->assertCount(1, $before);
        foreach ([Decision::MATCH, Decision::CREATE, Decision::EXPORT] as $resolution) {
            app(Review::class)->save([$r->id], ['review_status' => 'REVIEWED', 'resolution' => $resolution,
                'resolved_gt_curation_id' => $c->id], $this->actor->id);
        }
        $this->assertEquals($before, DB::table('curation_expert_panel')->where('curation_id', $c->id)->get()->toArray());
    }

}
