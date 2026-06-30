<?php

namespace Tests\Feature\DataExchange;

use App\ExpertPanel;
use App\IncomingStreamMessage;
use App\User;
use App\DataExchange\Actions\RemoveGpmMemberHandler;
use App\DataExchange\Actions\SyncGpmMemberHandler;
use App\Events\User\Created as UserCreated;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class GpmMemberHandlersTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        Event::fake([UserCreated::class]);
    }

    public function test_it_creates_a_user_and_assigns_current_roles(): void
    {
        $expertPanel = $this->createTestExpertPanel();
        $payload = $this->makePayload(
            'member_role_assigned',
            [
                [
                    'uuid' => '11111111-1111-4111-8111-111111111111',
                    'first_name' => 'Test',
                    'last_name' => 'Member',
                    'email' => 'member@example.org',
                    'roles' => ['Coordinator', 'Biocurator', 'Expert'],
                ],
            ],
            ['Coordinator', 'Biocurator']
        );

        $incomingMessage = $this->createIncomingMessage($payload, 401);
        app(SyncGpmMemberHandler::class)->handle($incomingMessage, $payload);
        $user = User::where('gpm_uuid', '11111111-1111-4111-8111-111111111111')->firstOrFail();
        $this->assertDatabaseHas('expert_panel_user', [
            'expert_panel_id' => $expertPanel->id,
            'user_id' => $user->id,
            'is_coordinator' => 1,
            'is_curator' => 1,
            'can_edit_curations' => 1,
        ]);
    }

    public function test_it_updates_roles_using_the_members_current_role_list(): void
    {
        $expertPanel = $this->createTestExpertPanel();
        $user = User::create([
            'name' => 'Test Member',
            'email' => 'member@example.org',
            'gpm_uuid' => '22222222-2222-4222-8222-222222222222',
        ]);

        $expertPanel->users()->attach($user->id, [
            'is_coordinator' => 1,
            'is_curator' => 1,
            'can_edit_curations' => 1,
        ]);

        $payload = $this->makePayload(
            'member_role_removed',
            [
                [
                    'uuid' => '22222222-2222-4222-8222-222222222222',
                    'first_name' => 'Test',
                    'last_name' => 'Member',
                    'email' => 'member@example.org',
                    'roles' => [
                        'Coordinator',
                        'Expert',
                    ],
                ],
            ],
            ['Biocurator']
        );

        $incomingMessage = $this->createIncomingMessage($payload, 402);
        app(SyncGpmMemberHandler::class)->handle($incomingMessage, $payload);
        $this->assertDatabaseHas('expert_panel_user', [
            'expert_panel_id' => $expertPanel->id,
            'user_id' => $user->id,
            'is_coordinator' => 1,
            'is_curator' => 0,
            'can_edit_curations' => 0,
        ]);
    }

    public function test_it_detaches_a_member_when_no_tracked_roles_remain(): void
    {
        $expertPanel = $this->createTestExpertPanel();
        $user = User::create([
            'name' => 'Test Member',
            'email' => 'member@example.org',
            'gpm_uuid' => '33333333-3333-4333-8333-333333333333',
        ]);

        $expertPanel->users()->attach($user->id, [
            'is_coordinator' => 0,
            'is_curator' => 1,
            'can_edit_curations' => 1,
        ]);

        $payload = $this->makePayload('member_role_removed', [
                [
                    'uuid' => '33333333-3333-4333-8333-333333333333',
                    'first_name' => 'Test',
                    'last_name' => 'Member',
                    'roles' => [
                        'Expert',
                    ],
                ],
            ],
            ['Biocurator']
        );

        $incomingMessage = $this->createIncomingMessage($payload, 403);
        app(SyncGpmMemberHandler::class)->handle($incomingMessage, $payload);
        $this->assertDatabaseMissing('expert_panel_user', [
            'expert_panel_id' => $expertPanel->id,
            'user_id' => $user->id,
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'gpm_uuid' => '33333333-3333-4333-8333-333333333333',
        ]);
    }

    public function test_it_detaches_a_removed_or_retired_member_without_deleting_the_user(): void
    {
        $expertPanel = $this->createTestExpertPanel();
        $user = User::create([
            'name' => 'Test Coordinator',
            'email' => 'coordinator@example.org',
            'gpm_uuid' => '44444444-4444-4444-8444-444444444444',
        ]);

        $expertPanel->users()->attach($user->id, [
            'is_coordinator' => 1,
            'is_curator' => 0,
            'can_edit_curations' => 0,
        ]);

        $payload = $this->makePayload('member_retired', [
                [
                    'uuid' => '44444444-4444-4444-8444-444444444444',
                    'first_name' => 'Test',
                    'last_name' => 'Coordinator',
                    'roles' => [
                        'Coordinator',
                    ],
                ],
            ]
        );

        $incomingMessage = $this->createIncomingMessage($payload, 404);
        app(RemoveGpmMemberHandler::class)->handle($incomingMessage, $payload);
        $this->assertDatabaseMissing('expert_panel_user', [
            'expert_panel_id' => $expertPanel->id,
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'gpm_uuid' => '44444444-4444-4444-8444-444444444444',
        ]);
    }

    public function test_it_ignores_a_new_member_without_a_tracked_role(): void
    {
        $this->createTestExpertPanel();
        $payload = $this->makePayload('member_added', [
                [
                    'uuid' => '55555555-5555-4555-8555-555555555555',
                    'first_name' => 'Test',
                    'last_name' => 'Expert',
                    'roles' => [
                        'Expert',
                    ],
                ],
            ]
        );

        $incomingMessage = $this->createIncomingMessage($payload, 405);
        app(SyncGpmMemberHandler::class)->handle($incomingMessage, $payload);
        $this->assertDatabaseMissing('users', [
            'gpm_uuid' => '55555555-5555-4555-8555-555555555555',
        ]);
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

    private function makePayload(string $eventType, array $members, array $changedRoles = []): array 
    {
        return [
            'event_type' => $eventType,
            'schema_version' => '2.0.1',
            'date' => now()->toDateTimeString(),
            'data' => [
                'members' => $members,
                'roles' => $changedRoles,
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
            'key' => 'test-member-message-'.$offset,
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