<?php

namespace App\Http\Controllers\Api;

use App\CurationType;
use Illuminate\Http\Request;

class CurationTypeController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $types = CurationType::all();

        return $types;
    }
}
