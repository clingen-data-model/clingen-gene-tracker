<?php

namespace App\Http\Controllers\Api;

use App\CurationType;
use App\Http\Requests\CurationTypeRequest;
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

    public function adminIndex(Request $request)
    {
        abort_unless($request->user()->hasPermissionTo('list curation-types'), 403);

        return CurationType::query()->orderBy('name')->get();
    }

    public function store(CurationTypeRequest $request)
    {
        $curationType = CurationType::create($request->validated());

        return response()->json($curationType, 201);
    }

    public function update(CurationTypeRequest $request, CurationType $curationType)
    {
        $curationType->update($request->validated());

        return $curationType->fresh();
    }

    public function destroy(Request $request, CurationType $curationType)
    {
        abort_unless($request->user()->hasPermissionTo('delete curation-types'), 403);

        if ($curationType->curations()->exists()) {
            return response()->json([
                'message' => 'This curation type is in use and cannot be deleted.',
            ], 409);
        }

        $curationType->delete();

        return response()->noContent();
    }
}
