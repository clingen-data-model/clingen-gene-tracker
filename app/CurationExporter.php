<?php

namespace App;

use Carbon\Carbon;
use Ramsey\Uuid\Uuid;

class CurationExporter
{
    private $curationStatuses;
    private $storage;

    public function __construct()
    {
        $this->curationStatuses = CurationStatus::all();
    }

    protected function buildQuery($params)
    {
        $query = Curation::with([
            'expertPanel', 
            'curationStatuses', 
            'curator', 
            'statuses', 
            'classifications'
        ]);
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

        if (\Auth::user()->hasAnyRole(['programmer', 'admin'])) {
            return $query;
        }

        if (\Auth::user()->isCoordinator()) {
            return $query;
        }

        $query->where('curator_id', \Auth::user()->id);

        return $query;
    }

    /**
     * @SuppressWarnings(PHPMD.ElseExpression) // used as callback in this class
     */
    public function getCsv($params = [], $csvPath = null)
    {
        $query = $this->buildQuery($params);

        $path = $csvPath ?? $this->buildFileName($params);

        $fh = fopen($path, 'w');
        fputcsv($fh, $this->buildHeader());
        $query->lazy()->each(function ($curation) use ($fh) {
            fputcsv($fh, $this->buildLine($curation));
        });
        fclose($fh);

        return $path;
    }

    /**
     * Filters $curation's statuses entries to the latest (by status_date) for each status
     * b/c we only want the latest instance of a status for the curtion export.
     */
    private function getLatestStatusDates($curation)
    {
        return $curation->statuses
        ->groupBy('id')
        ->map(function ($statusGroup) {
            return $statusGroup->sortByDesc('pivot.status_date')->first();
        })
        ->pluck('pivot.status_date', 'id');
    }

    private function buildFileName(array $params): string
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

        $path = storage_path($filename.'_at_'.now()->format('Y-m-d_H:i:s').'.csv');

        return $path;
    }

    private function buildLine(Curation $curation): array
    {
        $statuses = $this->getLatestStatusDates($curation);

        $line = [
            'Gene Symbol' => $curation->gene_symbol,
            'Expert Panel' => ($curation->expertPanel) ? $curation->expertPanel->name : null,
            'Curator' => ($curation->curator) ? $curation->curator->name : null,
            'Disease Entity' => $curation->mondo_id,
        ];

        $this->curationStatuses->each(function ($status) use (&$line, $statuses) {
            $line[$status->name.' date'] = (isset($statuses[$status->id]))
                                                ? $statuses[$status->id]->format('Y-m-d')
                                                : null;
        });

        $line['Classification'] = $curation->currentClassification->name;
        $line['Created'] = $curation->created_at;
        $line['GCI UUID'] = $curation->gdm_uuid;

        return $line;
    }

    private function buildHeader(): array
    {
        $header = [
            'Gene Symbol',
            'Expert Panel',
            'Curator',
            'Disease Entity',
        ];

        $header = array_merge(
            $header, 
            $this->curationStatuses->map(fn ($status) => $status->name.' date')->toArray()
        );

        $header[] = 'Classification';
        $header[] = 'Created';
        $header[] = 'GCI UUID';

        return $header;
    }
    
}
