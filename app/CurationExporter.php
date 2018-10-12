<?php

namespace App;

use Carbon\Carbon;

class CurationExporter
{
    public function getData($params = [])
    {
        $query = Curation::with('expertPanel', 'currentStatus', 'curator');
        if (isset($params['expert_panel_id'])) {
            $query->where('expert_panel_id', $params['expert_panel_id']);
        }
        if (isset($params['start_date'])) {
            $query->where('created_at', '>', Carbon::parse($params['start_date'])->startOfDay());
        }

        if (isset($params['end_date'])) {
            $query->where('created_at', '<', Carbon::parse($params['end_date'])->endOfDay());
        }
        
        return  $query->get()
                ->transform(function ($curation) {
                    return [
                        'Gene Symbol' => $curation->gene_symbol,
                        'Expert Panel' => $curation->expertPanel->name,
                        'Curator' => ($curation->curator) ? $curation->curator->name : null,
                        'Status' => $curation->currentStatus->name,
                        'Disease Entity' => $curation->mondo_id
                    ];
                });
    }

    public function getCsv($params = [], $csvPath = null)
    {
        $path = $csvPath ?? storage_path('exports/curations_export_'.now()->format('Y-m-d_H:i:s').'.csv');
        $data = $this->getData($params);
        $fh = fopen($path, 'w');
        fputcsv($fh, array_keys($data->first()));
        foreach ($data as $idx => $row) {
            fputcsv($fh, $row);
        }
        fclose($fh);
        return $path;
    }
}
