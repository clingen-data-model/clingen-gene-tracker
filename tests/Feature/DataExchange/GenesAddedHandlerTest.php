<?php

namespace Tests\Feature\DataExchange;

use App\Curation;
use App\ExpertPanel;
use App\IncomingStreamMessage;
use App\User;
use App\DataExchange\Actions\GenesAddedHandler;
use App\Events\User\Created as UserCreated;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class GenesAddedHandlerTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        // Prevent welcome emails from being sent.
        Event::fake([UserCreated::class]);

        // Run curation jobs immediately.
        config(['queue.default' => 'sync']);

        // Curation creation expects Uploaded status to have ID 1.
        DB::table('curation_statuses')->updateOrInsert(
            ['id' => 1],
            ['name' => 'Uploaded']
        );
    }

    public function test_it_adds_genes_to_a_gcep(): void
    {
        $expertPanel = $this->createTestExpertPanel();
        $coordinator = User::create([
            'name' => 'Test Coordinator',
            'email' => 'coordinator@example.org',
            'gpm_uuid' => '11111111-1111-4111-8111-111111111111',
        ]);

        $expertPanel->users()->syncWithoutDetaching([
            $coordinator->id => [
                'is_coordinator' => 1,
                'is_curator' => 0,
                'can_edit_curations' => 0,
            ],
        ]);

        $payload = $this->makePayload([
            [
                'hgnc_id' => 10483,
                'gene_symbol' => 'RYR1',
            ],
            [
                'hgnc_id' => 23045,
                'gene_symbol' => 'ARMC2',
            ],
        ]);

        $incomingMessage = $this->createIncomingMessage($payload, 201);
        app(GenesAddedHandler::class)->handle($incomingMessage, $payload);

        $this->assertDatabaseHas('curations', [
            'gene_symbol' => 'RYR1',
            'hgnc_id' => 10483,
            'expert_panel_id' => $expertPanel->id,
            'curator_id' => $coordinator->id,
            'incoming_stream_message_id' => $incomingMessage->id,
        ]);

        $this->assertDatabaseHas('curations', [
            'gene_symbol' => 'ARMC2',
            'hgnc_id' => 23045,
            'expert_panel_id' => $expertPanel->id,
            'curator_id' => $coordinator->id,
            'incoming_stream_message_id' => $incomingMessage->id,
        ]);

        $curation = Curation::where([
            'gene_symbol' => 'RYR1',
            'incoming_stream_message_id' => $incomingMessage->id,
        ])->firstOrFail();

        $this->assertDatabaseHas('curation_expert_panel', [
            'curation_id' => $curation->id,
            'expert_panel_id' => $expertPanel->id,
        ]);

        $this->assertDatabaseHas('curation_curation_status', [
            'curation_id' => $curation->id,
            'curation_status_id' => 1,
        ]);

        // Processing the same message again must not duplicate curations.
        app(GenesAddedHandler::class)->handle($incomingMessage, $payload);
        $this->assertSame(2, Curation::where('incoming_stream_message_id', $incomingMessage->id)->count());
    }

    private function createTestExpertPanel(): ExpertPanel
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
            'name' => 'Test Working Group',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $affiliationId = DB::table('affiliations')->insertGetId([
            'name' => 'Test GCEP',
            'short_name' => 'TestGCEP',
            'affiliation_type_id' => $affiliationTypeId,
            'parent_id' => null,
            'clingen_id' => 49999,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $expertPanelId = DB::table('expert_panels')->insertGetId([
            'name' => 'Test GCEP',
            'affiliation_id' => $affiliationId,
            'working_group_id' => $workingGroupId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return ExpertPanel::findOrFail($expertPanelId);
    }

    private function makePayload(array $genes): array
    {
        return [
            'event_type' => 'genes_added',
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
        $id = DB::table('incoming_stream_messages')->insertGetId([
            'topic' => 'gpm-general-events',
            'key' => 'test-genes-added-'.$offset,
            'partition' => 0,
            'offset' => $offset,
            'timestamp' => now()->timestamp,
            'error_code' => 0,
            'payload' => json_encode($payload),
            'gdm_uuid' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return IncomingStreamMessage::findOrFail($id);
    }
}