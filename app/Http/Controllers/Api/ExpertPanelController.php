<?php

namespace App\Http\Controllers\Api;

use App\ExpertPanel;
use App\Affiliation;
use App\Http\Requests\ExpertPanelRequest;
use Illuminate\Http\Request;

// use App\Http\Controllers\Controller;

class ExpertPanelController extends ApiController
{
    protected $modelClass = ExpertPanel::class;

    public function adminIndex(Request $request)
    {
        abort_unless($request->user()->hasPermissionTo('list expert-panels'), 403);

        $perPage = min(max((int) $request->input('per_page', 25), 1), 100);
        $search = trim($request->validate(['search' => ['nullable', 'string', 'max:200']])['search'] ?? '');

        return ExpertPanel::query()
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                ->where('name', 'like', '%'.$search.'%')
                ->orWhereHas('affiliation', fn ($query) => $query
                    ->where('name', 'like', '%'.$search.'%')
                    ->orWhere('short_name', 'like', '%'.$search.'%')
                    ->orWhere('clingen_id', 'like', '%'.$search.'%'))))
            ->with(['workingGroup:id,name', 'affiliation'])
            ->withCount(['curations', 'users'])
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function adminStore(ExpertPanelRequest $request)
    {
        $expertPanel = ExpertPanel::create($request->validated());

        return response()->json($this->loadAdminRelationships($expertPanel), 201);
    }

    public function adminOptions(Request $request)
    {
        abort_unless($request->user()->hasPermissionTo('list expert-panels'), 403);

        return ['affiliations' => Affiliation::query()->orderBy('name')->get(['id', 'name', 'short_name', 'clingen_id'])];
    }

    public function adminUpdate(ExpertPanelRequest $request, ExpertPanel $expertPanel)
    {
        $expertPanel->update($request->validated());

        return $this->loadAdminRelationships($expertPanel->fresh());
    }

    private function loadAdminRelationships(ExpertPanel $expertPanel): ExpertPanel
    {
        return $expertPanel
            ->load(['workingGroup:id,name', 'affiliation'])
            ->loadCount(['curations', 'users']);
    }

    public function index(Request $request)
    {
        if (!$request->has('sort')) {
            $request->merge([
                'sort' => [
                    'field' => 'name',
                    'dir' => 'asc'
                ]
            ]);
        }
        return parent::index($request);
    }

    public function show($id)
    {
        $panel = parent::show($id);
        $panel->load('users', 'users.roles', 'curations');

        return $panel;
    }
}
