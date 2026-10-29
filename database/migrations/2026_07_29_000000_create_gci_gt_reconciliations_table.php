<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGciGtReconciliationsTable extends Migration
{
    public function up()
    {
        Schema::create('gci_gt_reconciliations', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Run/source
            $table->char('run_uuid', 36)->index();
            $table->string('run_label')->nullable();
            $table->string('source_type', 30)->index();
            $table->string('source_filename');
            $table->char('source_hash', 64)->nullable();
            $table->unsignedInteger('source_row_number');

            // GCI values
            $table->unsignedInteger('gci_precuration_id')->nullable()->index();
            $table->char('gci_gdm_uuid', 36)->nullable()->index();
            $table->string('gci_gene_symbol')->nullable();
            $table->unsignedBigInteger('gci_affiliation_id')->nullable();
            $table->string('gci_disease_id')->nullable();
            $table->string('gci_mondo_id')->nullable();
            $table->string('gci_moi_hp_id', 50)->nullable();
            $table->boolean('gci_freetext_disease')->default(false);
            $table->json('raw_payload');

            // Matched GT snapshot
            $table->unsignedInteger('gt_curation_id')->nullable()->index();
            $table->char('gt_uuid', 36)->nullable();
            $table->char('gt_gdm_uuid', 36)->nullable();
            $table->string('gt_gene_symbol')->nullable();
            $table->unsignedBigInteger('gt_affiliation_id')->nullable();
            $table->unsignedInteger('gt_expert_panel_id')->nullable();
            $table->string('gt_expert_panel_name')->nullable();
            $table->string('gt_mondo_id')->nullable();
            $table->string('gt_moi_hp_id', 50)->nullable();
            $table->unsignedInteger('gt_curation_status_id')->nullable();
            $table->timestamp('gt_archived_at')->nullable();
            $table->timestamp('gt_deleted_at')->nullable();

            // Match classification
            $table->string('match_method', 30)->nullable()->index();
            $table->string('match_status', 40)->index();
            $table->boolean('is_confirmed_match')->default(false)->index();

            // Comparison flags: 1=match, 0=mismatch, NULL=not comparable
            $table->boolean('precuration_id_match')->nullable();
            $table->boolean('gdm_uuid_match')->nullable();
            $table->boolean('gene_match')->nullable();
            $table->boolean('affiliation_match')->nullable();
            $table->boolean('disease_match')->nullable();
            $table->boolean('moi_match')->nullable();
            $table->boolean('all_compared_fields_match')->nullable();

            // Warning/update flags
            $table->boolean('gdm_uuid_duplicate_in_gt')->default(false);
            $table->boolean('precuration_id_exists_deleted')->default(false);
            $table->boolean('has_identity_warning')->default(false)->index();
            $table->boolean('needs_gt_update')->default(false)->index();
            $table->boolean('needs_gdm_uuid_update')->default(false);
            $table->boolean('needs_disease_update')->default(false);
            $table->boolean('needs_moi_update')->default(false);
            $table->boolean('safe_update_candidate')->default(false)->index();

            // Investigation
            $table->json('conflicting_gt_curation_ids')->nullable();
            $table->json('issue_codes')->nullable();
            $table->string('review_status', 30)->default('PENDING')->index();
            $table->text('review_notes')->nullable();
            $table->string('resolution', 60)->nullable();

            $table->timestamps();

            $table->unique(
                ['run_uuid', 'source_type', 'source_row_number'],
                'gci_gt_recon_run_source_row_unique'
            );

            $table->index(
                ['run_uuid', 'match_status'],
                'gci_gt_recon_run_status_idx'
            );

            $table->index(
                ['run_uuid', 'needs_gt_update'],
                'gci_gt_recon_run_update_idx'
            );
        });
    }

    public function down()
    {
        Schema::dropIfExists('gci_gt_reconciliations');
    }
}
