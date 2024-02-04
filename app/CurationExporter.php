<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Barryvdh\Debugbar\Facades\Debugbar;

class CurationExporter
{
    private $curationStatuses;

    public function __construct()
    {
        $this->curationStatuses = CurationStatus::all();
    }

    protected function buildQuery($params)
    {
        $query = DB::table('curations')
                    ->select($this->getReportColumns())
                    ->join('expert_panels', 'curations.expert_panel_id', '=', 'expert_panels.id')
                    ->leftJoin('users', 'curations.curator_id', '=', 'users.id')
                    ->leftJoinSub($this->getLatestClassQuery(), 'latest_class', function ($join) {
                        $join->on('curations.id', '=', 'latest_class.curation_id');
                    })
                    ->leftJoinSub($this->buildStatusSubQuery(), 'status_dates', function ($join) {
                        $join->on('curations.id', '=', 'status_dates.curation_id');
                    })
                    ->orderBy('expert_panels.name', 'ASC')->orderBy('curations.gene_symbol', 'ASC')
                    ;

        if (isset($params['expert_panel_id'])) {
            $query->where('expert_panel_id', $params['expert_panel_id']);
        }

        if (isset($params['start_date']) || isset($params['end_date'])) {
            $query->whereExists(function ($q) use ($params) {
                $q->select(DB::raw(1))
                    ->from('curation_curation_status')
                    ->whereColumn('curation_curation_status.curation_id', 'curations.id');
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

    protected function buildQueryOld($params)
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

    private function getReportColumns(): array
    {
        $columns = array_merge(
            [
                'curations.gene_symbol as Gene Symbol',
                'expert_panels.name as Expert Panel',
                'users.name as Curator',
                'curations.mondo_id as Disease Entity',
            ], 
            $this->curationStatuses->map(fn ($status) => "{$status->name} Date")->toArray(),
            [
                'latest_class.name as Classification',
                'curations.created_at as Created',
                'curations.gdm_uuid as GCI UUID',
            ]
        );

        if (config('app.debug') && !app()->environment('testing')) {
            array_unshift($columns, 'curations.id as ID');
        }

        return $columns;
    }

    private function buildStatusSubQuery()
    {
        $statusColumns = ['curation_id', 'name',];
        $aggregateColumns = ['curation_id'];
        $this->curationStatuses->each(function ($status) use (&$statusColumns, &$aggregateColumns, &$statusDateColumns) {
            $statusColumns[] = DB::raw("CASE WHEN curation_status_id = {$status->id} THEN DATE(status_date) ELSE NULL END as `{$status->name}`");
            $aggregateColumns[] = DB::raw("MAX(`{$status->name}`) as `{$status->name} Date`");
        });

        $statusDateQuery = DB::table('curation_curation_status')
            ->select($statusColumns)
            ->join('curation_statuses', 'curation_curation_status.curation_status_id', '=', 'curation_statuses.id')
            ->whereIn(
                DB::Raw('(curation_id, status_date, curation_status_id)'),
                function ($query) {
                    $query->select('curation_id', DB::raw('max(status_date) as status_date'), 'curation_status_id')
                        ->from('curation_curation_status')
                        ->groupBy('curation_id', 'curation_status_id');
                }
            );

        return DB::query()
            ->from($statusDateQuery, 'status_date_query')
            ->select($aggregateColumns)
            ->groupBy('curation_id');
    }

    /**
     * @SuppressWarnings(PHPMD.ElseExpression) // used as callback in this class
     */
    public function getCsv($params = [], $csvPath = null)
    {
        $query = $this->buildQuery($params);
       
        $path = $csvPath ?? $this->buildFileName($params);

        $header = array_keys((array)$query->first());
        $fh = fopen($path, 'w');
        fputcsv($fh, $header);
        $query->lazy()->each(function ($row) use ($fh) {
            fputcsv($fh, (array)$row);
        });
        fclose($fh);

        return $path;
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

    private function getLatestClassQuery()
    {
        return DB::table('classification_curation')
            ->select('curation_id', 'classification_date', 'name')
            ->join('classifications', 'classification_curation.classification_id', '=', 'classifications.id')
            ->whereIn(
                DB::raw('(curation_id, classification_date)'),
                function ($query) {
                    $query->select('curation_id', DB::raw('max(classification_date) as classification_date'))
                        ->from('classification_curation')
                        ->groupBy('curation_id');
                }
            );
    }
}
