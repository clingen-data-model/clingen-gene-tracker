<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('gci_reconciliation_runs', function (Blueprint $t) {
            $t->uuid('uuid')->primary();
            $t->string('label')->nullable();
            $t->string('status')->default('DRAFT');
            $t->unsignedInteger('uploaded_by')->nullable();
            $t->unsignedInteger('started_by')->nullable();
            $t->timestamp('started_at')->nullable();
            $t->timestamp('completed_at')->nullable();
            $t->text('error')->nullable();
            $t->timestamps();
        });
        Schema::create('gci_reconciliation_uploads', function (Blueprint $t) {
            $t->id();
            $t->uuid('run_uuid')->index();
            $t->string('source_type', 30);
            $t->string('filename');
            $t->string('path');
            $t->char('sha256', 64);
            $t->unsignedInteger('record_count');
            $t->unsignedInteger('uploaded_by');
            $t->json('metadata')->nullable();
            $t->timestamps();
            $t->unique(['run_uuid', 'source_type'], 'gci_upload_run_source');
        });
        Schema::table('gci_gt_reconciliations', function (Blueprint $t) {
            $t->boolean('gt_is_retired')->default(false)->index();
            $t->json('deleted_gt_curation_ids')->nullable();
            $t->unsignedInteger('resolved_gt_curation_id')->nullable();
            $t->unsignedInteger('reviewed_by')->nullable();
            $t->timestamp('reviewed_at')->nullable();
            $t->string('execution_status', 30)->default('NOT_READY')->index();
            $t->unsignedInteger('executed_by')->nullable();
            $t->timestamp('executed_at')->nullable();
            $t->text('execution_error')->nullable();
            $t->timestamp('exported_at')->nullable();
            $t->unsignedInteger('exported_by')->nullable();
        });
        Schema::create('gci_reconciliation_audits', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('reconciliation_id')->index();
            $t->string('kind');
            $t->string('source')->default('gci_reconciliation');
            $t->string('source_event_key')->index();
            $t->string('source_id')->nullable();
            $t->unsignedInteger('actor_id');
            $t->string('action')->nullable();
            $t->json('before_values')->nullable();
            $t->json('after_values')->nullable();
            $t->text('error')->nullable();
            $t->timestamp('effective_at');
            $t->timestamps();
        });
        Schema::create('gci_reconciliation_previews', function (Blueprint $t) {
            $t->uuid('uuid')->primary();
            $t->unsignedInteger('actor_id');
            $t->json('plans');
            $t->timestamp('expires_at');
            $t->timestamp('consumed_at')->nullable();
            $t->timestamps();
        });
        // Preserve old rows, and make historical CLI runs selectable in the UI.
        foreach (\Illuminate\Support\Facades\DB::table('gci_gt_reconciliations')->select('run_uuid')->distinct()->pluck('run_uuid') as $uuid) {
            $rows = \Illuminate\Support\Facades\DB::table('gci_gt_reconciliations')->where('run_uuid', $uuid);
            \Illuminate\Support\Facades\DB::table('gci_reconciliation_runs')->insert([
                'uuid' => $uuid, 'label' => (clone $rows)->value('run_label'), 'status' => 'COMPLETED',
                'started_at' => (clone $rows)->min('created_at'), 'completed_at' => (clone $rows)->max('created_at'),
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        \Illuminate\Support\Facades\DB::table('gci_gt_reconciliations')
            ->where('gt_curation_status_id', config('curations.statuses.retired-assignment'))->update(['gt_is_retired' => true]);
    }
    public function down(): void
    {
        Schema::dropIfExists('gci_reconciliation_previews');
        Schema::dropIfExists('gci_reconciliation_audits');
        Schema::table('gci_gt_reconciliations', fn (Blueprint $t) => $t->dropColumn([
            'gt_is_retired', 'deleted_gt_curation_ids', 'resolved_gt_curation_id', 'reviewed_by', 'reviewed_at',
            'execution_status', 'executed_by', 'executed_at', 'execution_error', 'exported_at', 'exported_by',
        ]));
        Schema::dropIfExists('gci_reconciliation_uploads');
        Schema::dropIfExists('gci_reconciliation_runs');
    }
};
