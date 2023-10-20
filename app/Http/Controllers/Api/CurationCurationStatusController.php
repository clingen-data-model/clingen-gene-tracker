<?php

namespace App\Http\Controllers\Api;

use App\Curation;
use App\CurationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreCurationCurationStatusRequest;
use App\Http\Requests\Api\UpdateCurationCurationStatusRequest;
use App\Jobs\Curations\AddStatus;
use App\Jobs\Curations\UpdateCurrentStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Bus;

class CurationCurationStatusController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($curationId)
    {
        $curation = Curation::findOrFail($curationId);

        return $curation->curationStatuses;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(StoreCurationCurationStatusRequest $request, $curationId)
    {

        $curation = Curation::findOrFail($curationId);
        $status = CurationStatus::find($request->curation_status_id);
        AddStatus::dispatchSync($curation, $status, $request->status_date);

        return $curation->curationStatuses()
            ->where('curation_status_id', $curation->curation_status_id)
            ->limit(1)
            ->get()
            ->last();
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($curationId, $curationStatusId)
    {
        return Curation::findOrFail($curationId)
            ->curationStatuses
            ->firstWhere('id', $curationStatusId);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateCurationCurationStatusRequest $request, $curationId, $curationCurationStatusId)
    {
        $curation = Curation::findOrFail($curationId);

        $relatedStatus = $curation->curationStatuses
            ->firstWhere('pivot.id', $curationCurationStatusId);

        $relatedStatus->pivot->update([
            'status_date' => $request->status_date,
        ]);

        UpdateCurrentStatus::dispatch($curation);

        return $relatedStatus;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     */
    public function destroy($curationId, $pivotId): JsonResponse
    {
        $curation = Curation::findOrFail($curationId);

        $curation->statuses
            ->firstWhere('pivot.id', $pivotId)
            ->pivot
            ->delete();

        Bus::dispatchSync(new UpdateCurrentStatus($curation));

        return response()->json([], 204);
    }
}
