<?php

namespace App\Http\Controllers\Api;

use App\ExpertPanel;
use Illuminate\Http\Request;

// use App\Http\Controllers\Controller;

class ExpertPanelController extends ApiController
{
    protected $modelClass = ExpertPanel::class;

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
