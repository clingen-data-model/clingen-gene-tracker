<?php

namespace App\DataExchange\Actions;

use App\ExpertPanel;
use App\User;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class SyncGpmExpertPanelMember
{
    public function sync(ExpertPanel $expertPanel, array $member): ?User
    {
        $roles = collect($member['roles'] ?? []);
        $isCoordinator = $roles->contains('Coordinator');
        $isBiocurator = $roles->contains('Biocurator');

        // GT only tracks GPM Coordinators and Biocurators.
        if (!$isCoordinator && !$isBiocurator) {
            $this->remove($expertPanel, $member);
            return null;
        }

        if (empty($member['email'])) {
            $this->notifyMissingEmail($expertPanel, $member);
            return null;
        }

        $gpmUuid = $member['uuid'] ?? null;
        $email = strtolower(trim($member['email']));
        if (!$gpmUuid) { throw new RuntimeException('A GPM member is missing their UUID.'); }
        
        $user = User::where('gpm_uuid', $gpmUuid)->first();
        if (!$user) { $user = User::whereRaw('LOWER(email) = ?', [$email])->first(); }

        if ($user && $user->gpm_uuid && $user->gpm_uuid !== $gpmUuid) {
            throw new RuntimeException('The GT user with email '.$email.' is already linked to another GPM UUID.');
        }

        if (!$user) {
            $user = User::create([
                'gpm_uuid' => $gpmUuid,
                'name' => $this->getMemberName($member),
                'email' => $email,
            ]);
        } else {
            $updates = [];

            if (!$user->gpm_uuid) { $updates['gpm_uuid'] = $gpmUuid; }
            if ($user->name !== $this->getMemberName($member)) { $updates['name'] = $this->getMemberName($member); }
            if (strtolower($user->email) !== $email) { $updates['email'] = $email; }
            if ($updates) { $user->update($updates); }
        }

        $expertPanel->users()->syncWithoutDetaching([
            $user->id => [
                'is_coordinator' => $isCoordinator,
                'is_curator' => $isBiocurator,
                'can_edit_curations' => $isBiocurator,
            ],
        ]);
        return $user;
    }

    public function remove(ExpertPanel $expertPanel, array $member): void
    {
        $user = $this->findUser($member);
        if (!$user) { return; }
        $expertPanel->users()->detach($user->id);
    }

    private function findUser(array $member): ?User
    {
        $gpmUuid = $member['uuid'] ?? null;
        if ($gpmUuid) {
            $user = User::where('gpm_uuid', $gpmUuid)->first();
            if ($user) { return $user; }
        }

        if (!empty($member['email'])) {
            return User::whereRaw(
                'LOWER(email) = ?',
                [strtolower(trim($member['email']))]
            )->first();
        }
        return null;
    }

    // IN CASE WE NEED ANY OTHER CLEANUPS WITH USER'S NAME
    private function getMemberName(array $member): string
    {
        return trim(($member['first_name'] ?? '<') . ' ' . ($member['last_name'] ?? ''));
    }

    private function notifyMissingEmail(ExpertPanel $expertPanel, array $member): void
    {
        Log::channel('slack')->warning('GT could not synchronize a GPM Coordinator or Biocurator because their email was missing.', [
                'expert_panel' => $expertPanel->name,
                'affiliation_id' => $expertPanel->affiliation->clingen_id,
                'member_uuid' => $member['uuid'] ?? null,
                'member_name' => $this->getMemberName($member),
                'roles' => $member['roles'] ?? [],
            ]
        );
    }
}