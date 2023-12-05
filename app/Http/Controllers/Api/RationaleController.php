<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\RationaleResource;
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
        // return RationaleResource::collection(Rationale::all());
        return Rationale::all();
    }
}
