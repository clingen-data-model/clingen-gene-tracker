<?php

namespace App\Http\Controllers\Api;

use App\Rationale;
use Illuminate\Http\Request;

class RationaleController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return Rationale::all();
    }
}
