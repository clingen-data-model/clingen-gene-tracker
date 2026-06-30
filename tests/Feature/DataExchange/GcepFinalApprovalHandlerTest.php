<?php

namespace Tests\Feature\DataExchange;

use App\Curation;
use App\ExpertPanel;
use App\IncomingStreamMessage;
use App\User;
use App\DataExchange\Actions\GcepFinalApprovalHandler;
use App\Events\User\Created as UserCreated;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class GcepFinalApprovalHandlerTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        // Prevent newly created users from receiving welcome emails.
        Event::fake([UserCreated::class]);

        // Run curation jobs immediately during the test.
        config(['queue.default' => 'sync']);

        // Curation::boot() expects the Uploaded status to have ID 1.
        DB::table('curation_statuses')->updateOrInsert(['id' => 1], ['name' => 'Uploaded']);
    }

    public function test_it_imports_gcep_genes_and_members(): void
    {
        $expertPanel = $this->createTestExpertPanel();
        $payload = $this->makePayload(
            members: [
                [
                    'uuid' => '11111111-1111-4111-8111-111111111111',
                    'first_name' => 'Test',
                    'last_name' => 'Coordinator',
                    'email' => 'coordinator@example.org',
                    'roles' => ['Coordinator'],
                ],
                [
                    'uuid' => '22222222-2222-4222-8222-222222222222',
                    'first_name' => 'Test',
                    'last_name' => 'Biocurator',
                    'email' => 'biocurator@example.org',
                    'roles' => ['Biocurator'],
                ],
            ],
            genes: [
                [
                    'hgnc_id' => 23045,
                    'gene_symbol' => 'ARMC2',
                ],
                [
                    'hgnc_id' => 26684,
                    'gene_symbol' => 'CFAP43',
                ],
            ]
        );

        $incomingMessage = $this->createIncomingMessage($payload, offset: 1);
        app(GcepFinalApprovalHandler::class)->handle($incomingMessage, $payload);
        $coordinator = User::where('gpm_uuid', '11111111-1111-4111-8111-111111111111')->firstOrFail();
        $biocurator = User::where('gpm_uuid', '22222222-2222-4222-8222-222222222222')->firstOrFail();

        $this->assertDatabaseHas('expert_panel_user', ['expert_panel_id' => $expertPanel->id, 'user_id' => $coordinator->id, 'is_coordinator' => 1, 'is_curator' => 0, 'can_edit_curations' => 0]);
        $this->assertDatabaseHas('expert_panel_user', ['expert_panel_id' => $expertPanel->id, 'user_id' => $biocurator->id, 'is_coordinator' => 0, 'is_curator' => 1, 'can_edit_curations' => 1]);
        $this->assertDatabaseHas('curations', ['gene_symbol' => 'ARMC2', 'hgnc_id' => 23045, 'expert_panel_id' => $expertPanel->id, 'curator_id' => $coordinator->id, 'incoming_stream_message_id' => $incomingMessage->id]);
        $this->assertDatabaseHas('curations', ['gene_symbol' => 'CFAP43', 'hgnc_id' => 26684, 'expert_panel_id' => $expertPanel->id, 'curator_id' => $coordinator->id, 'incoming_stream_message_id' => $incomingMessage->id]);
        $armc2Curation = Curation::where(['gene_symbol' => 'ARMC2', 'incoming_stream_message_id' => $incomingMessage->id])->firstOrFail();
        $this->assertDatabaseHas('curation_expert_panel', ['curation_id' => $armc2Curation->id, 'expert_panel_id' => $expertPanel->id]);
        $this->assertDatabaseHas('curation_curation_status', ['curation_id' => $armc2Curation->id, 'curation_status_id' => 1]);

        // Processing the same incoming message again must not create duplicate curations.
        app(GcepFinalApprovalHandler::class)->handle($incomingMessage, $payload);
        $this->assertSame( 1, Curation::where([
                'gene_symbol' => 'ARMC2',
                'incoming_stream_message_id' => $incomingMessage->id,
            ])->count()
        );

        $this->assertSame(2, Curation::where(
                'incoming_stream_message_id',
                $incomingMessage->id
            )->count()
        );
    }

    public function test_it_links_an_existing_user_by_email(): void
    {
        $expertPanel = $this->createTestExpertPanel();
        $existingUser = User::create([
            'name' => 'Existing Coordinator',
            'email' => 'existing@example.org',
        ]);

        $payload = $this->makePayload(members: [
                [
                    'uuid' => '33333333-3333-4333-8333-333333333333',
                    'first_name' => 'Existing',
                    'last_name' => 'Coordinator',
                    'email' => 'existing@example.org',
                    'roles' => ['Coordinator'],
                ],
            ]
        );

        $incomingMessage = $this->createIncomingMessage($payload, offset: 2);
        app(GcepFinalApprovalHandler::class)->handle($incomingMessage, $payload);
        $existingUser->refresh();
        $this->assertSame('33333333-3333-4333-8333-333333333333', $existingUser->gpm_uuid);
        $this->assertSame(1, User::where('email', 'existing@example.org')->count());
        $this->assertDatabaseHas('expert_panel_user', [
            'expert_panel_id' => $expertPanel->id,
            'user_id' => $existingUser->id,
            'is_coordinator' => 1,
            'is_curator' => 0,
            'can_edit_curations' => 0,
        ]);
    }

    public function test_it_assigns_both_roles_to_the_same_member(): void
    {
        $expertPanel = $this->createTestExpertPanel();
        $payload = $this->makePayload(members: [
                [
                    'uuid' => '44444444-4444-4444-8444-444444444444',
                    'first_name' => 'Dual',
                    'last_name' => 'Role',
                    'email' => 'dual@example.org',
                    'roles' => ['Coordinator','Biocurator'],
                ],
            ]
        );
        $incomingMessage = $this->createIncomingMessage($payload, offset: 3);
        app(GcepFinalApprovalHandler::class)->handle($incomingMessage, $payload);
        $user = User::where('gpm_uuid', '44444444-4444-4444-8444-444444444444')->firstOrFail();
        $this->assertDatabaseHas('expert_panel_user', [
            'expert_panel_id' => $expertPanel->id,
            'user_id' => $user->id,
            'is_coordinator' => 1,
            'is_curator' => 1,
            'can_edit_curations' => 1,
        ]);
    }

    private function createTestExpertPanel(int $clingenId = 49999): ExpertPanel 
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
                'clingen_id' => $clingenId,
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

    private function makePayload(array $members = [], array $genes = [], string $affiliationId = '49999'): array 
    {
        return [
            'event_type' => 'gcep_final_approval',
            'schema_version' => '2.0.1',
            'date' => now()->toDateTimeString(),
            'data' => [
                'group' => [
                    'expert_panel' => [
                        'affiliation_id' => $affiliationId,
                        'name' => 'Test GCEP',
                        'type' => 'gcep',
                    ],
                ],
                'members' => $members,
                'scope' => [
                    'genes' => $genes,
                ],
            ],
        ];
    }

    private function createIncomingMessage(array $payload, int $offset): IncomingStreamMessage 
    {
        $incomingMessageId = DB::table('incoming_stream_messages')->insertGetId([
            'topic' => 'gpm-general-events',
            'key' => 'test-gcep-final-approval-'.$offset,
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