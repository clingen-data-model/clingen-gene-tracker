<?php

namespace App\Http\Controllers\Api;

use App\ExpertPanel;
use App\Http\Requests\ExpertPanelRequest;
use Illuminate\Http\Request;

// use App\Http\Controllers\Controller;

class ExpertPanelController extends ApiController
{
    protected $modelClass = ExpertPanel::class;

    public function adminIndex(Request $request)
    {
        abort_unless($request->user()->hasPermissionTo('list expert-panels'), 403);

        return ExpertPanel::query()
            ->with(['workingGroup:id,name', 'affiliation'])
            ->withCount(['curations', 'users'])
            ->orderBy('name')
            ->get();
    }

    public function adminStore(ExpertPanelRequest $request)
    {
        $expertPanel = ExpertPanel::create($request->validated());

        return response()->json($this->loadAdminRelationships($expertPanel), 201);
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
