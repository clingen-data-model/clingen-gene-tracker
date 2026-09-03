<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\RationaleResource;
use App\Http\Requests\RationaleRequest;
use App\Rationale;
use Illuminate\Support\Facades\DB;
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

    public function adminIndex(Request $request)
    {
        abort_unless($request->user()->hasPermissionTo('list rationales'), 403);

        return Rationale::query()->orderBy('name')->get();
    }

    public function store(RationaleRequest $request)
    {
        return response()->json(Rationale::create($request->validated()), 201);
    }

    public function update(RationaleRequest $request, Rationale $rationale)
    {
        $rationale->update($request->validated());

        return $rationale->fresh();
    }

    public function destroy(Request $request, Rationale $rationale)
    {
        abort_unless($request->user()->hasPermissionTo('delete rationales'), 403);

        $isReferenced = $rationale->curations()->exists()
            || DB::table('curation_rationale')->where('rationale_id', $rationale->id)->exists();
        if ($isReferenced) {
            return response()->json([
                'message' => 'This rationale is in use and cannot be deleted.',
            ], 409);
        }

        $rationale->delete();

        return response()->noContent();
    }
}
