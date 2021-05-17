<?php

namespace App\Http\Controllers\Api;

use App\Curation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Jobs\Curations\SetOwner;
use App\Http\Requests\Curations\TransferRequest;

class CurationTransferController extends Controller
{
    public function store($curationId, TransferRequest $request)
    {
        $curation = Curation::findOrFail($curationId);

        if (!Auth::user()->can('transfer', $curation)) {
            return response(['error' => 'You do not have permission to transfer ownership of this curation'], 403);
        }

        $job = new SetOwner(
            $curation,
            $request->expert_panel_id,
            $request->start_date,
            $request->end_date
        );
        Bus::dispatch($job);

        $ownerRecords = $curation->expertPanels
                ->sortByDesc('pivot.start_date')
                ->map(function ($epRel) {
                    return [
                        'id' => $epRel->id,
                        'name' => $epRel->name,
                        'affiliation_id' => $epRel->affiliation_id,
                        'working_group_id' => $epRel->working_group_id,
                        'start_date' => $epRel->pivot->start_date,
                        'end_date' => $epRel->pivot->end_date,
                    ];
                });
        return ['curation_id' => $curation->id, 'expert_panels' => $ownerRecords->values()];
    }
}
