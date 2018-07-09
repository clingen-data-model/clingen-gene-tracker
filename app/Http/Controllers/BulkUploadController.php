<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Jobs\BulkCurationProcessor;
use Illuminate\Support\Facades\Bus;
use Maatwebsite\Excel\Facades\Excel;

class BulkUploadController extends Controller
{
    public function show()
    {
        return view('bulk_uploads.show');
    }

    public function upload(Request $request)
    {
        $path = $request->file('bulk_curations')->store('bulk_curation_uploads');

        Excel::selectSheets('Curations')->load(storage_path('app/'.$path), function ($reader) {
            $rows = $reader->get();
            foreach ($rows as $idx => $row) {
                if (!$row->gene_symbol) {
                    continue;
                }
            }
        });
    }
}
