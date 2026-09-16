<?php

namespace App\Http\Controllers\Api;

use App\Affiliation;
use App\ExpertPanel;
use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminRevisionController extends Controller
{
    public function users(Request $request, User $user)
    {
        abort_unless($request->user()->hasPermissionTo('list users'), 403);

        return $this->history($user, ['name', 'email', 'deactivated_at', 'gci_uuid', 'affiliation_id']);
    }

    public function expertPanels(Request $request, ExpertPanel $expertPanel)
    {
        abort_unless($request->user()->hasPermissionTo('list expert-panels'), 403);

        return $this->history($expertPanel, ['name', 'working_group_id', 'affiliation_id']);
    }

    public function affiliations(Affiliation $affiliation)
    {
        // Like the affiliation list, access is governed by the Admin shell middleware.
        return $this->history($affiliation, ['clingen_id', 'name', 'short_name', 'affiliation_type_id', 'parent_id']);
    }

    private function history(Model $model, array $fields): array
    {
        // Explicit display fields keep historical credentials and internal data out of the API.
        $revisions = $model->revisionHistory()
            ->whereIn('key', array_merge($fields, ['created_at', 'updated_at', 'deleted_at']))
            ->orderByDesc('created_at')->orderByDesc('id')
            ->get(['id', 'key', 'old_value', 'new_value', 'user_id', 'created_at']);
        $actors = User::withTrashed()->whereIn('id', $revisions->pluck('user_id')->filter()->unique())
            ->pluck('name', 'id');

        return ['data' => $revisions->map(fn ($revision) => [
            'id' => $revision->id,
            'field' => match ($revision->key) {
                'clingen_id' => 'ClinGen ID',
                'gci_uuid' => 'GCI UUID',
                default => Str::of($revision->key)->replace('_', ' ')->title()->replace(' Id', ' ID')->toString(),
            },
            'old_value' => $revision->old_value,
            'new_value' => $revision->new_value,
            'changed_by' => $revision->user_id
                ? ($actors->get($revision->user_id) ?? 'Unknown user (ID '.$revision->user_id.')')
                : 'System / unknown',
            'changed_at' => $revision->created_at?->toIso8601String(),
        ])];
    }
}
