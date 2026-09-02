<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Turns the three curation history pivots into an idempotent event log.
 *
 * Each pivot row is an assertion that a field took a value at a date. Recording
 * which source event produced it, and enforcing uniqueness on that key in the
 * database, is what makes replaying a Kafka message a no-op instead of a
 * duplicate row.
 */
return new class extends Migration
{
    /**
     * Value column and date column for each history pivot.
     */
    private array $pivots = [
        'curation_curation_status' => ['curation_status_id', 'status_date'],
        'classification_curation' => ['classification_id', 'classification_date'],
        'curation_expert_panel' => ['expert_panel_id', 'start_date'],
    ];

    private array $indexNames = [
        'curation_curation_status' => ['ccs_source_key_unique', 'ccs_value_date_unique', 'ccs_timeline_idx'],
        'classification_curation' => ['cc_source_key_unique', 'cc_value_date_unique', 'cc_timeline_idx'],
        'curation_expert_panel' => ['cep_source_key_unique', 'cep_value_date_unique', 'cep_timeline_idx'],
    ];

    public function up(): void
    {
        $this->removeOrphanedExpertPanelRows();

        foreach ($this->pivots as $table => [$valueColumn, $dateColumn]) {
            $this->deleteDuplicates($table, $valueColumn, $dateColumn);
            $this->addColumns($table);
            $this->backfillSourceKeys($table);
            $this->addIndexes($table, $valueColumn, $dateColumn);
        }

        // curation_expert_panel was created without any of these.
        Schema::table('curation_expert_panel', function (Blueprint $table) {
            $table->index('curation_id', 'cep_curation_id_idx');
            $table->index('expert_panel_id', 'cep_expert_panel_id_idx');
            $table->foreign('curation_id', 'cep_curation_id_foreign')
                ->references('id')->on('curations')->cascadeOnDelete();
            $table->foreign('expert_panel_id', 'cep_expert_panel_id_foreign')
                ->references('id')->on('expert_panels')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('curation_expert_panel', function (Blueprint $table) {
            $table->dropForeign('cep_curation_id_foreign');
            $table->dropForeign('cep_expert_panel_id_foreign');
            $table->dropIndex('cep_curation_id_idx');
            $table->dropIndex('cep_expert_panel_id_idx');
        });

        foreach ($this->pivots as $table => $columns) {
            [$sourceKeyUnique, $valueDateUnique, $timelineIndex] = $this->indexNames[$table];

            Schema::table($table, function (Blueprint $blueprint) use ($sourceKeyUnique, $valueDateUnique, $timelineIndex) {
                $blueprint->dropUnique($sourceKeyUnique);
                $blueprint->dropUnique($valueDateUnique);
                $blueprint->dropIndex($timelineIndex);
                $blueprint->dropColumn(['source', 'source_event_key']);
            });
        }
    }

    /**
     * Keep the earliest row of each duplicate set. The unique indexes below cannot
     * be created while duplicates exist, and they are duplicates by definition:
     * the same field taking the same value at the same instant.
     */
    private function deleteDuplicates(string $table, string $valueColumn, string $dateColumn): void
    {
        DB::delete(
            "DELETE dupe FROM `{$table}` dupe
                JOIN `{$table}` keeper ON keeper.id < dupe.id
            WHERE keeper.curation_id = dupe.curation_id
                AND keeper.`{$valueColumn}` = dupe.`{$valueColumn}`
                AND keeper.`{$dateColumn}` = dupe.`{$dateColumn}`"
        );
    }

    private function removeOrphanedExpertPanelRows(): void
    {
        DB::delete(
            'DELETE p FROM curation_expert_panel p
                LEFT JOIN curations c ON c.id = p.curation_id
            WHERE c.id IS NULL'
        );

        DB::delete(
            'DELETE p FROM curation_expert_panel p
                LEFT JOIN expert_panels e ON e.id = p.expert_panel_id
            WHERE e.id IS NULL'
        );
    }

    private function addColumns(string $table): void
    {
        Schema::table($table, function (Blueprint $blueprint) {
            // ascii keeps the unique index at ~227 bytes rather than ~896 under utf8mb4.
            $blueprint->string('source', 32)->charset('ascii')->nullable()->after('curation_id');
            $blueprint->string('source_event_key', 191)->charset('ascii')->nullable()->after('source');
        });
    }

    /**
     * Existing rows predate source keys. Deriving one from the row id is
     * deterministic, unique by construction, and re-runnable.
     */
    private function backfillSourceKeys(string $table): void
    {
        DB::update(
            "UPDATE `{$table}`
                SET `source` = 'backfill',
                    `source_event_key` = CONCAT('legacy:{$table}:', `id`)
            WHERE `source_event_key` IS NULL"
        );

        Schema::table($table, function (Blueprint $blueprint) {
            $blueprint->string('source', 32)->charset('ascii')->nullable(false)->change();
            // NOT NULL matters: MySQL allows unlimited NULLs in a unique index, so a
            // nullable key would let writers slip past the constraint entirely.
            $blueprint->string('source_event_key', 191)->charset('ascii')->nullable(false)->change();
        });
    }

    private function addIndexes(string $table, string $valueColumn, string $dateColumn): void
    {
        [$sourceKeyUnique, $valueDateUnique, $timelineIndex] = $this->indexNames[$table];

        Schema::table($table, function (Blueprint $blueprint) use (
            $valueColumn,
            $dateColumn,
            $sourceKeyUnique,
            $valueDateUnique,
            $timelineIndex
        ) {
            // Hard idempotency: one source event asserts one value for one curation.
            // Scoped to the curation rather than global so a single GCI message key can
            // be reused verbatim across the three pivots.
            $blueprint->unique(['curation_id', 'source_event_key'], $sourceKeyUnique);
            $blueprint->unique(['curation_id', $valueColumn, $dateColumn], $valueDateUnique);
            $blueprint->index(['curation_id', $dateColumn, 'id'], $timelineIndex);
        });
    }
};
