<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ApiController extends Controller
{
    protected $modelClass;

    public function index(Request $request)
    {
        $query = $this->getBaseQuery();
        if ($request->with) {
            $query->with($request->with);
        }

        return $query->get();
    }

    public function show($id)
    {
        $group = $this->getModelClass()::find($id);

        return $group;
    }

    protected function getBaseQuery()
    {
        return $this->modelClass::query();
    }

    protected function getModelClass()
    {
        return $this->modelClass;
    }
}
