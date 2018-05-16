<?php

namespace App\Http\Controllers\Api;

use App\ExpertPanel;

// use App\Http\Controllers\Controller;

class ExpertPanelController extends ApiController
{
    protected $modelClass = ExpertPanel::class;

    public function show($id)
    {
        $panel = parent::show($id);
        $panel->load('users', 'users.roles', 'topics');

        return $panel;
    }
}
