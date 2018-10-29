<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Curation;

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
        $curation->statuses()->attach([
            $request->curation_status_id => [
                'status_date' => $request->status_date
            ]
        ]);

        return $curation->curationStatuses;
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
            ->firstWhere('pivot.id', $curationCurationStatusId)
            ;
        $relatedStatus->pivot->update([
            'status_date' => $request->status_date
        ]);
        
        return $relatedStatus;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
