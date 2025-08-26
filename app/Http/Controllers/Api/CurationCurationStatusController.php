<?php

namespace App\Http\Controllers\Api;

use App\Curation;
use App\CurationStatus;
use Illuminate\Http\Request;
use App\Jobs\Curations\AddStatus;
use Illuminate\Support\Facades\Bus;
use App\Http\Controllers\Controller;
use App\Jobs\Curations\UpdateCurrentStatus;
use Illuminate\Support\Facades\Log;

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
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, $curationId)
    {
        $request->validate([
            'curation_status_id' => 'required|exists:curation_statuses,id',
            'status_date' => 'nullable|date_format:Y-m-d'
        ]);

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
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $curationId, $curationCurationStatusId)
    {
        $request->validate([
            'status_date' => 'date_format:Y-m-d'
        ]);
        $curation = Curation::findOrFail($curationId);
               
        $relatedStatus = $curation->curationStatuses
            ->firstWhere('pivot.id', $curationCurationStatusId);

        $relatedStatus->pivot->update([
            'status_date' => $request->status_date
        ]);

        UpdateCurrentStatus::dispatch($curation);
        
        return $relatedStatus;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($curationId, $pivotId)
    {
        $curation = Curation::findOrFail($curationId);
        
        $deleted = $curation->statuses
            ->firstWhere('pivot.id', $pivotId)
            ?->pivot
            ?->delete();

        // Refresh the relationship to get the latest statuses
        $curation->load('statuses');

        if ($curation->statuses->isNotEmpty()) {
            Bus::dispatchSync(new UpdateCurrentStatus($curation));
        } else {
            Log::info("Skipping UpdateCurrentStatus: no statuses left for curation ID {$curation->id}");
        }
    }
}
