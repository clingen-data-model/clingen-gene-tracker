<?php

namespace App\Http\Controllers\Api;

use App\Classification;
use App\Http\Controllers\Controller;

class ClassificationController extends Controller
{
    public function index()
    {
        return Classification::select('id', 'name', 'slug')->get();
    }
}
