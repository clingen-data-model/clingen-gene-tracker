<?php
namespace Tests\Feature;

use App\{Affiliation, Curation, Disease, ExpertPanel, Gene, ModeOfInheritance, User};
use App\Services\GciReconciliation\{Decision, GciReconciliationExecutor, PayloadReader, Reconciler, Record, Run, Workflow};
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class GciReconciliationTest extends TestCase
{
    private array $paths = [];
    private $actor;
    private $panel;
    private $gene;
    private $disease;
    private $moi;
    private GciReconciliationExecutor $executor;

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
        $this->executor = app(GciReconciliationExecutor::class);
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
        Run::create(['uuid' => $uuid, 'status' => 'COMPLETED']);
        app(Reconciler::class)->reconcile($uuid, 'test', ...$paths);
        return Record::where('run_uuid', $uuid)->orderBy('id')->get();
    }
    private function approve(Record $r, string $resolution, ?int $resolved = null): void
    {
        $this->executor->review([$r->id], ['review_status' => 'REVIEWED', 'resolution' => $resolution,
            'review_notes' => 'Investigated', 'resolved_gt_curation_id' => $resolved], $this->actor->id);
    }
    private function apply(Record $r): array
    {
        $preview = $this->executor->preview([$r->id], $this->actor->id);
        $this->assertEmpty($preview['errors']);
        return $this->executor->execute($preview['uuid'], $this->actor->id);
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
        $this->assertNotEmpty(app(Workflow::class)->candidates($rows[5]));
        $this->assertFalse(Record::filtered(['run_uuid' => $rows[2]->run_uuid])->whereKey($rows[2]->id)->exists());
        $this->assertTrue(Record::filtered(['include_retired' => true])->whereKey($rows[2]->id)->exists());
    }
    public function test_safe_disease_and_moi_updates_preview_audit_and_idempotency(): void
    {
        foreach (['disease', 'moi', 'both'] as $case) {
            $c = $this->curation(['mondo_id' => $case === 'moi' ? $this->disease->mondo_id : null, 'moi_id' => $case === 'disease' ? $this->moi->id : null]);
            $r = $this->reconcile([$this->item(['precurationId' => (string) $c->id, 'PK' => $c->gdm_uuid])], [])->first();
            $this->assertTrue($r->safe_update_candidate);
            $this->approve($r, Decision::UPDATE);
            $this->assertSame($this->actor->id, $r->fresh()->reviewed_by);
            $this->assertNotNull($r->fresh()->reviewed_at);
            $before = $c->fresh()->only(['mondo_id', 'moi_id']);
            $preview = $this->executor->preview([$r->id], $this->actor->id);
            $this->assertSame($before, $c->fresh()->only(['mondo_id', 'moi_id']));
            $result = $this->executor->execute($preview['uuid'], $this->actor->id);
            $this->assertSame('APPLIED', $result[0]['status'], json_encode($result));
            $this->assertSame($this->disease->mondo_id, $c->fresh()->mondo_id);
            $this->assertSame($this->moi->id, $c->fresh()->moi_id);
            $this->assertSame($r->disease_match, $r->fresh()->disease_match);
            $this->assertNotEmpty($this->executor->preview([$r->id], $this->actor->id)['errors']);
            $this->assertDatabaseHas('gci_reconciliation_audits', ['reconciliation_id' => $r->id, 'kind' => 'APPLIED', 'actor_id' => $this->actor->id]);
        }
    }
    public function test_uuid_correction_requires_review(): void
    {
        $c = $this->curation(); $item = $this->item(['precurationId' => (string) $c->id]);
        $r = $this->reconcile([$item], [])->first();
        $this->assertFalse($r->safe_update_candidate);
        $this->assertNotEmpty($this->executor->preview([$r->id], $this->actor->id)['errors']);
        $this->approve($r, Decision::UUID);
        $this->assertSame('APPLIED', $this->apply($r)[0]['status']);
        $this->assertSame($item['PK'], $c->fresh()->gdm_uuid);
    }
    public function test_creation_uses_uploaded_status_and_exports_original_schema(): void
    {
        $item = $this->item(); $r = $this->reconcile([], [$item])->first();
        $this->approve($r, Decision::CREATE);
        $start = now()->startOfSecond();
        $result = $this->apply($r);
        $this->assertSame('APPLIED', $result[0]['status'], json_encode($result));
        $c = Curation::findOrFail($r->fresh()->resolved_gt_curation_id);
        $this->assertSame(config('curations.statuses.uploaded'), $c->curation_status_id);
        $this->assertSame($this->panel->affiliation->parent_id, $c->affiliation_id);
        $this->assertTrue(\Carbon\Carbon::parse($c->current_status_date)->gte($start));
        $payload = app(Workflow::class)->export([$r->id], $this->actor->id);
        $this->assertEquals($item + ['precurationId' => (string) $c->id], $payload['Items'][0]);
        $this->assertSame(1, $payload['Count']);
        $this->assertArrayHasKey('ScannedCount', $payload);
        $this->assertNotNull($r->fresh()->exported_at);
    }
    public function test_direct_and_manually_resolved_exports_exclude_unresolved(): void
    {
        $c = $this->curation(); $manual = $this->curation();
        $rows = $this->reconcile([], [$this->item(['PK' => $c->gdm_uuid]), $this->item(), $this->item()]);
        $this->approve($rows[1], Decision::MATCH, $manual->id); $this->apply($rows[1]);
        $payload = app(Workflow::class)->export($rows->modelKeys(), $this->actor->id);
        $this->assertSame(2, $payload['Count']);
        $this->assertSame([(string) $c->id, (string) $manual->id], array_column($payload['Items'], 'precurationId'));
        $this->assertNull($rows[1]->fresh()->gt_curation_id);
    }
    public function test_stale_preview_failure_does_not_corrupt_other_record(): void
    {
        $a = $this->curation(['mondo_id' => null]); $b = $this->curation(['mondo_id' => null]);
        $rows = $this->reconcile([$this->item(['PK' => $a->gdm_uuid, 'precurationId' => $a->id]), $this->item(['PK' => $b->gdm_uuid, 'precurationId' => $b->id])], []);
        foreach ($rows as $r) $this->approve($r, Decision::UPDATE);
        $preview = $this->executor->preview($rows->modelKeys(), $this->actor->id);
        DB::table('curations')->where('id', $a->id)->update(['mondo_id' => 'MONDO:1234567']);
        $result = $this->executor->execute($preview['uuid'], $this->actor->id);
        $this->assertSame(['FAILED', 'APPLIED'], array_column($result, 'status'));
        $this->assertSame('MONDO:1234567', $a->fresh()->mondo_id);
        $this->assertSame($this->disease->mondo_id, $b->fresh()->mondo_id);
        $this->assertSame('FAILED', $rows[0]->fresh()->execution_status);
    }
    public function test_missing_mappings_and_ambiguous_affiliation_prevent_creation(): void
    {
        $r = $this->reconcile([], [$this->item()])->first(); $this->approve($r, Decision::CREATE);
        $this->disease->update(['is_obsolete' => true]);
        $this->assertNotEmpty($this->executor->preview([$r->id], $this->actor->id)['errors']);
        $this->disease->update(['is_obsolete' => false]);
        $r->update(['gci_moi_hp_id' => 'HP:9999999']);
        $this->assertNotEmpty($this->executor->preview([$r->id], $this->actor->id)['errors']);
        $r->update(['gci_moi_hp_id' => $this->moi->hp_id]);
        factory(ExpertPanel::class)->create(['affiliation_id' => $this->panel->affiliation_id]);
        $this->assertNotEmpty($this->executor->preview([$r->id], $this->actor->id)['errors']);
        $this->assertFalse(Curation::where('gdm_uuid', $r->gci_gdm_uuid)->exists());
    }
    public function test_upload_pair_and_duplicate_hash_are_validated_and_reconciled(): void
    {
        $run = Run::create(['uuid' => (string) Str::uuid(), 'status' => 'DRAFT']);
        $workflow = app(Workflow::class);
        foreach ([PayloadReader::HAS => [$this->item(['precurationId' => '4294967294'])], PayloadReader::NO => [$this->item()]] as $type => $items) {
            $contents = json_encode(['Items' => $items]);
            $upload = $workflow->upload($run, UploadedFile::fake()->createWithContent('anything.json', $contents), $type, $this->actor->id);
            $this->paths[] = $upload->path;
            $same = $workflow->upload($run, UploadedFile::fake()->createWithContent('renamed.json', $contents), $type, $this->actor->id);
            $this->assertSame($upload->id, $same->id);
            $this->assertStringStartsWith(storage_path('app/'), $upload->path);
        }
        $workflow->reconcile($run, $this->actor->id);
        $this->assertSame('COMPLETED', $run->fresh()->status);
        $this->assertSame(2, Record::where('run_uuid', $run->uuid)->count());
    }
    public function test_invalid_review_combination_is_rejected(): void
    {
        $r = $this->reconcile([], [$this->item()])->first();
        $this->expectException(ValidationException::class);
        $this->approve($r, Decision::UPDATE);
    }
    public function test_deleted_precuration_id_is_detected_but_never_matched(): void
    {
        $c = $this->curation(); $c->delete();
        $r = $this->reconcile([$this->item(['PK' => $c->gdm_uuid, 'precurationId' => $c->id])], [])->first();
        $this->assertNull($r->gt_curation_id);
        $this->assertEquals(1, $r->precuration_id_exists_deleted);
        $this->assertSame('NOT_FOUND_IN_GT', $r->match_status);
    }
    public function test_duplicate_input_and_affiliation_warnings_exclude_safe_updates(): void
    {
        $c = $this->curation(['mondo_id' => null]);
        $item = $this->item(['PK' => $c->gdm_uuid, 'precurationId' => $c->id]);
        $rows = $this->reconcile([$item, $item], []);
        $this->assertTrue($rows[0]->has_identity_warning);
        $this->assertFalse($rows[0]->safe_update_candidate);
        $row = $this->reconcile([array_merge($item, ['affiliation' => '998899'])], [])->first();
        $this->assertFalse($row->safe_update_candidate);
        $this->assertTrue($row->has_identity_warning);
    }
    public function test_creation_mapping_failure_after_preview_is_recorded_without_partial_creation(): void
    {
        $r = $this->reconcile([], [$this->item()])->first();
        $this->approve($r, Decision::CREATE);
        $preview = $this->executor->preview([$r->id], $this->actor->id);
        $this->assertEmpty($preview['errors']);
        DB::table('diseases')->where('id', $this->disease->id)->update(['is_obsolete' => true]);
        $result = $this->executor->execute($preview['uuid'], $this->actor->id);
        $this->assertSame('FAILED', $result[0]['status']);
        $this->assertStringContainsString('Disease', $r->fresh()->execution_error);
        $this->assertFalse(Curation::where('gdm_uuid', $r->gci_gdm_uuid)->exists());
        $this->assertDatabaseHas('gci_reconciliation_audits', ['reconciliation_id' => $r->id, 'kind' => 'FAILED']);
    }
    public function test_unknown_gene_prevents_creation(): void
    {
        $r = $this->reconcile([], [$this->item(['gene' => 'UNKNOWN_GCI_GENE'])])->first();
        $this->approve($r, Decision::CREATE);
        $preview = $this->executor->preview([$r->id], $this->actor->id);
        $this->assertStringContainsString('Gene', $preview['errors'][0]['error']);
    }
    public function test_preview_cannot_be_replayed(): void
    {
        $c = $this->curation(['mondo_id' => null]);
        $r = $this->reconcile([$this->item(['PK' => $c->gdm_uuid, 'precurationId' => $c->id])], [])->first();
        $this->approve($r, Decision::UPDATE);
        $preview = $this->executor->preview([$r->id], $this->actor->id);
        $this->executor->execute($preview['uuid'], $this->actor->id);
        $this->expectException(ValidationException::class);
        $this->executor->execute($preview['uuid'], $this->actor->id);
    }
    public function test_no_action_and_deleted_targets_do_not_export(): void
    {
        $a = $this->curation(); $b = $this->curation();
        $rows = $this->reconcile([], [$this->item(['PK' => $a->gdm_uuid]), $this->item(['PK' => $b->gdm_uuid])]);
        $this->approve($rows[0], 'NO_ACTION'); $b->delete();
        $this->expectException(ValidationException::class);
        app(Workflow::class)->export($rows->modelKeys(), $this->actor->id);
    }
    public function test_execution_permission_is_independent_from_review_permission(): void
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->actor->assignRole('admin');
        $role = \Spatie\Permission\Models\Role::findByName('admin');
        $role->revokePermissionTo('execute gci-reconciliation');
        $this->actingAs($this->actor, 'api')->postJson('/api/admin/gci-reconciliation/preview', ['ids' => [1]])->assertForbidden();
        $this->actingAs($this->actor, 'api')->getJson('/api/admin/gci-reconciliation/runs')->assertOk();
        $other = factory(User::class)->create();
        $this->actingAs($other, 'api')->getJson('/api/admin/gci-reconciliation/runs')->assertForbidden();
    }
}

