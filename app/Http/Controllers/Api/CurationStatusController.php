<?php

namespace App\Http\Controllers\Api;

use App\CurationStatus;
use App\Http\Requests\CurationStatusRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CurationStatusController extends ApiController
{
    protected $modelClass = CurationStatus::class;

    public function adminIndex(Request $request)
    {
        abort_unless($request->user()->hasPermissionTo('list curation-statuses'), 403);

        return CurationStatus::query()->orderBy('name')->get();
    }

    public function store(CurationStatusRequest $request)
    {
        return response()->json(CurationStatus::create($request->validated()), 201);
    }

    public function update(CurationStatusRequest $request, CurationStatus $curationStatus)
    {
        $curationStatus->update($request->validated());

        return $curationStatus->fresh();
    }

    public function destroy(Request $request, CurationStatus $curationStatus)
    {
        abort_unless($request->user()->hasPermissionTo('delete curation-statuses'), 403);

        $isReferenced = DB::table('curations')->where('curation_status_id', $curationStatus->id)->exists()
            || DB::table('curation_curation_status')->where('curation_status_id', $curationStatus->id)->exists()
            || DB::table('gci_curations')->where('status_id', $curationStatus->id)->exists();
        if ($isReferenced) {
            return response()->json([
                'message' => 'This curation status is in use and cannot be deleted.',
            ], 409);
        }

        $curationStatus->delete();

        return response()->noContent();
    }
}
