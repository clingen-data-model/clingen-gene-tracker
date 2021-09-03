<?php

namespace App\Http\Controllers\Api;

use App\Curation;
use App\Jobs\AddNote;
use Illuminate\Http\Request;
use App\Jobs\Curations\SetOwner;
use Illuminate\Support\Facades\Bus;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Curations\TransferRequest;

class CurationTransferController extends Controller
{
    public function store($curationId, TransferRequest $request)
    {
        $curation = Curation::findOrFail($curationId);

        if (!Auth::user()->can('transfer', $curation)) {
            return response(['error' => 'You do not have permission to transfer ownership of this curation'], 403);
        }

        Bus::dispatch(new SetOwner(
            $curation,
            $request->expert_panel_id,
            $request->start_date,
            $request->end_date,
        ));

        if ($request->notes) {
            Bus::dispatch(new AddNote($curation, $request->notes, 'curation transfer', \Auth::user()));
        }

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
                        'pivot' => $epRel->pivot
                    ];
                });
        return ['curation_id' => $curation->id, 'expert_panels' => $ownerRecords->values()];
    }
}
