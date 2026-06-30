<?php

namespace App\DataExchange\Actions;

use App\ExpertPanel;
use App\IncomingStreamMessage;
use RuntimeException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class GcepFinalApprovalHandler
{
    public function __construct(
        private ResolveGpmExpertPanel $resolveExpertPanel,
        private SyncGpmExpertPanelMember $syncMember,
        private CreateGpmCuration $createCuration
    ) {
    }

    public function handle(IncomingStreamMessage $incomingMessage, array $payload ): void 
    {
        DB::transaction(function () use ($incomingMessage, $payload) {
            $this->process($incomingMessage, $payload);
        });
    }

    private function process(IncomingStreamMessage $incomingMessage, array $payload): void 
    {
        $expertPanel = $this->resolveExpertPanel->handle($payload);
        $affiliationId = $expertPanel->affiliation->clingen_id;
        $members = collect(data_get($payload, 'data.members', []));
        $missingEmailMembers = collect();
        $syncedMembers = collect();

        foreach ($members as $member) {
            $roles = collect($member['roles'] ?? []);
            $hasRelevantRole = $roles->contains('Coordinator') || $roles->contains('Biocurator');
            if (!$hasRelevantRole) { continue; }

            if (empty($member['email'])) {
                $missingEmailMembers->push([
                    'uuid' => $member['uuid'] ?? null,
                    'name' => trim(($member['first_name'] ?? '') . ' ' . ($member['last_name'] ?? '')),
                    'roles' => $member['roles'] ?? [],
                ]);
                continue;
            }
            $user = $this->syncMember->sync($expertPanel, $member);
            if ($user) {
                $syncedMembers->push([
                    'user' => $user,
                    'is_coordinator' => in_array('Coordinator', $member['roles'] ?? [], true),
                    'is_biocurator' => in_array('Biocurator', $member['roles'] ?? [], true),
                ]);
            }
        }

        $coordinatorRecord = $syncedMembers->first(fn (array $record) => $record['is_coordinator']);
        $coordinator = $coordinatorRecord['user'] ?? null;
        if (!$coordinator) {
            Log::channel('slack')->warning('GT could not find a Coordinator in a GCEP final approval event.', [
                    'affiliation_id' => $affiliationId,
                    'expert_panel' => $expertPanel->name,
                    'incoming_stream_message_id' => $incomingMessage->id,
                ]
            );
        }
        $genes = collect(data_get($payload, 'data.scope.genes', []));
        foreach ($genes as $gene) {
            $this->createCuration->handle($expertPanel, $incomingMessage, $gene, $coordinator);
        }
        if ($missingEmailMembers->isNotEmpty()) {
            Log::channel('slack')->warning('GT skipped GPM Coordinators or Biocurators with missing email addresses.', [
                    'affiliation_id' => $affiliationId,
                    'expert_panel' => $expertPanel->name,
                    'incoming_stream_message_id' => $incomingMessage->id,
                    'members' => $missingEmailMembers->all(),
                ]
            );
        }

        Log::info('Processed GPM gcep_final_approval event.', [
            'affiliation_id' => $affiliationId,
            'expert_panel_id' => $expertPanel->id,
            'incoming_stream_message_id' => $incomingMessage->id,
            'genes_received' => $genes->count(),
            'members_synchronized' => $syncedMembers->count(),
        ]);
    }
}