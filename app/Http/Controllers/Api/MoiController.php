<?php

namespace App\Http\Controllers\Api;

use App\ModeOfInheritance;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class MoiController extends Controller
{
    public function index()
    {
        return ModeOfInheritance::select('id', 'name', 'hp_id', 'parent_id')->get();
    }
}
