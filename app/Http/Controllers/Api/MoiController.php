<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MoiAdminRequest;
use App\ModeOfInheritance;
use Illuminate\Http\Request;

class MoiController extends Controller
{
    public function index()
    {
        return ModeOfInheritance::select('id', 'name', 'abbreviation', 'hp_id', 'parent_id')->curatable()->get();
    }

    public function adminIndex(Request $request)
    {
        abort_unless($request->user()->hasPermissionTo('list mois'), 403);

        return ModeOfInheritance::query()
            ->with('parent:id,name')
            ->orderBy('name')
            ->get();
    }

    public function adminUpdate(MoiAdminRequest $request, ModeOfInheritance $moi)
    {
        $moi->update($request->validated());

        return $moi->fresh()->load('parent:id,name');
    }
}
