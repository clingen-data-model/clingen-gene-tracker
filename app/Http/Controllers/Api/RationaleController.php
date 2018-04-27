<?php

namespace App\Http\Controllers\Api;

use App\Rationale;

class RationaleController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Rationale::all();
    }
}
