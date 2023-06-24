<?php

namespace App\Http\Controllers;

class CurationByGdmController extends Controller
{
    public function __invoke($gdmUuid)
    {
        $curation = \App\Curation::findByGdmUuid($gdmUuid);
        if (! $curation) {
            return response('curation not found', 404);
        }

        return response()->redirectTo('/#/curations/'.$curation->id);
    }
}
