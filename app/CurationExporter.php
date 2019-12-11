<?php

namespace App;

use Carbon\Carbon;
use App\ExpertPanel;

class CurationExporter
{
    protected function buildQuery($params)
    {
        $query = Curation::with('expertPanel', 'curationStatuses', 'curator', 'statuses', 'classifications');
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

        if (\Auth::user()->hasAnyRole(['programmer','admin'])) {
            return $query;
        }

        if (\Auth::user()->isCoordinator()) {
            return $query;
        }

        $query->where('curator_id', \Auth::user()->id);

        return $query;
    }

    public function getData($params = [])
    {
        $query = $this->buildQuery($params);
        $curationStatuses = CurationStatus::all();

        return  $query->get()
                ->transform(function ($curation) use ($curationStatuses) {
                    $statuses = $curation->statuses->pluck('pivot.status_date', 'id');

                    $line = [
                        'Gene Symbol' => $curation->gene_symbol,
                        'Expert Panel' => ($curation->expertPanel) ? $curation->expertPanel->name : null,
                        'Curator' => ($curation->curator) ? $curation->curator->name : null,
                        'Disease Entity' => $curation->mondo_id,
                    ];

                    $curationStatuses->each(function ($status) use (&$line, $statuses) {
                        $line[$status->name.' date']  = (isset($statuses[$status->id]))
                                                            ? $statuses[$status->id]->format('Y-m-d')
                                                            : null;
                    });

                    $line['Classification'] = $curation->currentClassification->name;
                    $line['Created'] = $curation->created_at;

                    return $line;
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
