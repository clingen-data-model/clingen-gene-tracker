<?php

namespace App;

use Carbon\Carbon;
use App\ExpertPanel;

class CurationExporter
{
    public function getData($params = [])
    {
        $query = Curation::with('expertPanel', 'curationStatuses', 'curator', 'statuses');
        if (isset($params['expert_panel_id'])) {
            $query->where('expert_panel_id', $params['expert_panel_id']);
        }
        if (isset($params['start_date']) || isset($params['end_date'])) {
            $query->whereHas('statuses', function ($q) use ($params) {
                if (isset($params['start_date'])) {
                    $q->where('status_date', '>', Carbon::parse($params['start_date'])->startOfDay());
                }
                if (isset($params['end_date'])) {
                    $q->where('status_date', '<', Carbon::parse($params['end_date'])->endOfDay());
                }
            });
        }
        
        return  $query->get()
                ->transform(function ($curation) {
                    $statuses = $curation->statuses->pluck('pivot.status_date', 'id');
                    
                    return [
                        'Gene Symbol' =>                    $curation->gene_symbol,
                        'Expert Panel' =>                   ($curation->expertPanel) ? $curation->expertPanel->name : null,
                        'Curator' =>                        ($curation->curator) ? $curation->curator->name : null,
                        'Disease Entity' =>                 $curation->mondo_id,
                        'Uploaded date' =>                  (isset($statuses[1])) ? $statuses[1]->format('Y-m-d') : null,
                        'Precuration date' =>               (isset($statuses[2])) ? $statuses[2]->format('Y-m-d') : null,
                        'Disease entity assigned date' =>   (isset($statuses[3])) ? $statuses[3]->format('Y-m-d') : null,
                        'Curation In Progress date' =>      (isset($statuses[4])) ? $statuses[4]->format('Y-m-d') : null,
                        'Curation Provisional date' =>      (isset($statuses[5])) ? $statuses[5]->format('Y-m-d') : null,
                        'Curation Approved date' =>         (isset($statuses[6])) ? $statuses[6]->format('Y-m-d') : null,
                        'Recuration assigned date' =>       (isset($statuses[7])) ? $statuses[7]->format('Y-m-d') : null,
                        'Created' =>                        $curation->created_at->format('Y-m-d')
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
