<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class CurationByGdmController extends Controller
{
    public function __invoke($gdmUuid): Response
    {
        $curation = \App\Curation::findByGdmUuid($gdmUuid);
        if (! $curation) {
            return response('curation not found', 404);
        }

        return redirect()->to('/#/curations/'.$curation->id);
    }
}
