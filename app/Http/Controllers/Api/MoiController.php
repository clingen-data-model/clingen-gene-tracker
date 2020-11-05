<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\ModeOfInheritance;

class MoiController extends Controller
{
    public function index()
    {
        return ModeOfInheritance::select('id', 'name', 'hp_id', 'parent_id')->curatable()->get();
    }
}
