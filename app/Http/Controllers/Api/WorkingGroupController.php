<?php

namespace App\Http\Controllers\Api;

use App\WorkingGroup;

class WorkingGroupController extends ApiController
{
    protected $modelClass = WorkingGroup::class;

    public function show($id)
    {
        $group = parent::show($id);
        $group->load('expertPanels', 'expertPanels.topics');

        return $group;
    }
}
