<?php

namespace App\DataExchange\Actions;

use App\IncomingStreamMessage;
use Illuminate\Support\Facades\DB;

class SyncGpmMemberHandler
{
    public function __construct(private ResolveGpmExpertPanel $resolveExpertPanel, private SyncGpmExpertPanelMember $syncMember) 
    {
    }

    public function handle(IncomingStreamMessage $incomingMessage, array $payload): void
    {
        DB::transaction(function () use ($payload) {
            $expertPanel = $this->resolveExpertPanel->handle($payload);
            $members = data_get($payload, 'data.members', []);
            foreach ($members as $member) {
                $this->syncMember->sync($expertPanel, $member);
            }
        });
    }
}