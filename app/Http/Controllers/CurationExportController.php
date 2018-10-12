<?php

namespace App\Http\Controllers;

use App\ExpertPanel;
use App\CurationExporter;
use Illuminate\Http\Request;

class CurationExportController extends Controller
{
    protected $exporter;

    public function __construct(CurationExporter $exporter)
    {
        $this->exporter = $exporter;
    }



    public function getCsv(Request $request)
    {
        return response()->download($this->exporter->getCsv($request->all()));
    }
}
