<?php

namespace App\Http\Controllers\Api;

use App\Affiliation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AffiliationRequest;
use Illuminate\Http\Request;

class AffiliationController extends Controller
{
    public function adminIndex(Request $request)
    {
        abort_unless($request->user()->hasAnyRole(['admin', 'programmer']), 403);

        $perPage = min(max((int) $request->input('per_page', 25), 1), 100);
        $search = trim($request->validate(['search' => ['nullable', 'string', 'max:200']])['search'] ?? '');

        return Affiliation::query()
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                ->where('name', 'like', '%'.$search.'%')
                ->orWhere('short_name', 'like', '%'.$search.'%')
                ->orWhere('clingen_id', 'like', '%'.$search.'%')))
            ->with([
                'type:id,name',
                'parent:id,name,short_name,clingen_id',
                'expertPanel:id,name,affiliation_id',
            ])
            ->withCount('expertPanel')
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function adminUpdate(AffiliationRequest $request, Affiliation $affiliation)
    {
        $affiliation->update($request->validated());

        return $this->loadAdminRelationships($affiliation->fresh());
    }

    private function loadAdminRelationships(Affiliation $affiliation): Affiliation
    {
        return $affiliation
            ->load([
                'type:id,name',
                'parent:id,name,short_name,clingen_id',
                'expertPanel:id,name,affiliation_id',
            ])
            ->loadCount('expertPanel');
    }
}
