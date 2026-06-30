<?php

namespace Tests\Feature\DataExchange;

use App\Curation;
use App\ExpertPanel;
use App\IncomingStreamMessage;
use App\DataExchange\Actions\GeneRemovedHandler;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class GeneRemovedHandlerTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        DB::table('curation_statuses')->updateOrInsert(
            ['id' => 1],
            ['name' => 'Uploaded']
        );
    }

    public function test_it_soft_deletes_only_matching_gpm_curations(): void
    {
        $expertPanel = $this->createTestExpertPanel(49999);
        $otherExpertPanel = $this->createTestExpertPanel(49998);

        $sourceMessage = $this->createIncomingMessage(['event_type' => 'genes_added'], 301);
        $gpmCuration = $this->createCuration(
            $expertPanel->id,
            'ANAPC1',
            19988,
            $sourceMessage->id
        );

        $manualCuration = $this->createCuration(
            $expertPanel->id,
            'ANAPC1',
            19988
        );

        $otherPanelCuration = $this->createCuration(
            $otherExpertPanel->id,
            'ANAPC1',
            19988,
            $sourceMessage->id
        );

        $payload = $this->makePayload([
            [
                'hgnc_id' => 19988,
                'gene_symbol' => 'ANAPC1',
            ],
        ]);

        $removalMessage = $this->createIncomingMessage($payload, 302);
        app(GeneRemovedHandler::class)->handle($removalMessage, $payload);
        $this->assertNotNull(Curation::withTrashed()->findOrFail($gpmCuration->id)->deleted_at);
        $this->assertNull(Curation::withTrashed()->findOrFail($manualCuration->id)->deleted_at);
        $this->assertNull(Curation::withTrashed()->findOrFail($otherPanelCuration->id)->deleted_at);

        // Reprocessing the message should not cause an error.
        app(GeneRemovedHandler::class)->handle($removalMessage, $payload);
        $this->assertSame(1, Curation::onlyTrashed()
                ->where('expert_panel_id', $expertPanel->id)
                ->where('hgnc_id', 19988)
                ->whereNotNull('incoming_stream_message_id')
                ->count()
        );
    }

    public function test_it_can_match_a_gene_by_symbol_when_hgnc_id_is_missing(): void
    {
        $expertPanel = $this->createTestExpertPanel(49999);
        $sourceMessage = $this->createIncomingMessage(['event_type' => 'genes_added'], 303);
        $curation = $this->createCuration(
            $expertPanel->id,
            'ANAPC1',
            19988,
            $sourceMessage->id
        );

        $payload = $this->makePayload([
            [
                'gene_symbol' => 'ANAPC1',
            ],
        ]);

        $removalMessage = $this->createIncomingMessage($payload, 304);
        app(GeneRemovedHandler::class)->handle($removalMessage, $payload);
        $this->assertNotNull(Curation::withTrashed()->findOrFail($curation->id)->deleted_at);
    }

    private function createTestExpertPanel(int $clingenId): ExpertPanel
    {
        $affiliationTypeId = DB::table('affiliation_types')->where('name', 'gcep')->value('id');
        if (!$affiliationTypeId) {
            $affiliationTypeId = DB::table('affiliation_types')->insertGetId([
                'name' => 'gcep',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $workingGroupId = DB::table('working_groups')->insertGetId([
            'name' => 'Test Working Group '.$clingenId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $affiliationId = DB::table('affiliations')->insertGetId([
            'name' => 'Test GCEP '.$clingenId,
            'short_name' => 'TestGCEP'.$clingenId,
            'affiliation_type_id' => $affiliationTypeId,
            'parent_id' => null,
            'clingen_id' => $clingenId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $expertPanelId = DB::table('expert_panels')->insertGetId([
            'name' => 'Test GCEP '.$clingenId,
            'affiliation_id' => $affiliationId,
            'working_group_id' => $workingGroupId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return ExpertPanel::findOrFail($expertPanelId);
    }

    private function createCuration(int $expertPanelId, string $geneSymbol, int $hgncId, ?int $incomingMessageId = null): Curation 
    {
        $curationId = DB::table('curations')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'gene_symbol' => $geneSymbol,
            'hgnc_id' => $hgncId,
            'curation_status_id' => 1,
            'expert_panel_id' => $expertPanelId,
            'incoming_stream_message_id' => $incomingMessageId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return Curation::findOrFail($curationId);
    }

    private function makePayload(array $genes): array
    {
        return [
            'event_type' => 'gene_removed',
            'schema_version' => '2.0.1',
            'date' => now()->toDateTimeString(),
            'data' => [
                'genes' => $genes,
                'group' => [
                    'type' => 'gcep',
                    'expert_panel' => [
                        'affiliation_id' => '49999',
                        'name' => 'Test GCEP',
                        'type' => 'gcep',
                    ],
                ],
            ],
        ];
    }

    private function createIncomingMessage(array $payload, int $offset): IncomingStreamMessage 
    {
        $incomingMessageId = DB::table('incoming_stream_messages')->insertGetId([
            'topic' => 'gpm-general-events',
            'key' => 'test-gene-message-'.$offset,
            'partition' => 0,
            'offset' => $offset,
            'timestamp' => now()->timestamp,
            'error_code' => 0,
            'payload' => json_encode($payload),
            'gdm_uuid' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return IncomingStreamMessage::findOrFail($incomingMessageId);
    }
}