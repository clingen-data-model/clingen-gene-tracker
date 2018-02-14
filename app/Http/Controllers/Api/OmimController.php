<?php

namespace App\Http\Controllers\Api;

use App\Clients\OmimClient;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class OmimController extends Controller
{
    protected $omim;

    public function __construct()
    {
        $this->omim = new OmimClient();
    }

    public function entry(Request $request)
    {
        $entry = $this->omim->getEntry($request->mim_number);
        return $entry;
    }

    public function search(Request $request)
    {
        $searchResults = $this->omim->search($request->all());
        return $searchResults;
    }
}
