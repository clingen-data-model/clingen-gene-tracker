<?php

namespace App;

use Carbon\Carbon;
use App\ExpertPanel;

class CurationExporter
{
    public function getData($params = [])
    {
        $query = Curation::with('expertPanel', 'curationStatuses', 'curator');
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
                        'Expert Panel' => ($curation->expertPanel) ? $curation->expertPanel->name : null,
                        'Curator' => ($curation->curator) ? $curation->curator->name : null,
                        'Status' => ($curation->currentStatus) ? $curation->currentStatus->name : null,
                        'Disease Entity' => $curation->mondo_id,
                        'Created' => $curation->created_at->format('Y-m-d')
                    ];
                });
    }

    public function getCsv($params = [], $csvPath = null)
    {
        $filename = 'exports/curations_export';
        
        if (isset($params['expert_panel_id'])) {
            $panel = ExpertPanel::findOrFail($params['expert_panel_id']);
            $filename .= '_'.$panel->fileSafeName;
        }
        if (isset($params['start_date'])) {
            $filename .= '_from_'.$params['start_date'];
        }
        if (isset($params['end_date'])) {
            $filename .= '_to_'.$params['end_date'];
        }

        $path = $csvPath ?? storage_path($filename.'_at_'.now()->format('Y-m-d_H:i:s').'.csv');
        $data = $this->getData($params);
        $fh = fopen($path, 'w');
        if ($data->count() > 0) {
            fputcsv($fh, array_keys($data->first()));
            foreach ($data as $idx => $row) {
                fputcsv($fh, $row);
            }
        } else {
            fputcsv($fh, ['Gene Symbol','Expert Panel', 'Curator', 'Status', 'Disease Entity', 'Created']);
        }
        fclose($fh);
        return $path;
    }
}
