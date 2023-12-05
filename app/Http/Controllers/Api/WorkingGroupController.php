<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\WorkingGroupResource;
use App\WorkingGroup;

class WorkingGroupController extends ApiController
{
    protected $modelClass = WorkingGroup::class;

    public function show($id)
    {
        $group = parent::show($id);
        $group->load(
            'expertPanels',
            'expertPanels.curations',
            'expertPanels.curations.curator',
            'expertPanels.curations.curationStatuses',
            'expertPanels.curations.classifications',
            'expertPanels.curations.expertPanel',
            'expertPanels.users',
            'expertPanels.users.roles'
        );

        return new WorkingGroupResource($group);
    }
}
