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
            $with = $request->with;
            if (is_string($request->with)) {
                $with = explode(',', $request->with);
            }
            $query->with($with);
        }
        if ($request->has('sort')) {
            $field = isset($request->sort['field']) ? $request->sort['field'] : 'id';
            $dir = isset($request->sort['dir']) ? $request->sort['dir'] : 'asc';
            $query->orderBy($field, $dir);
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
