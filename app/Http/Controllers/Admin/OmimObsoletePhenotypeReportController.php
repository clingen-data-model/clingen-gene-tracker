<?php

namespace App\Http\Controllers\Admin;

use App\Curation;
use App\Phenotype;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Response;

class OmimObsoletePhenotypeReportController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'phenotypes'); // phenotypes || curations ||used
        $perPage = $request->get('per_page', 20);

        if ($request->get('download') === 'csv') {
            return $this->downloadCsv($request, $tab);
        }

        $obsoletePhenotypesCount = Phenotype::where('obsolete', true)->count();

        $affectedCurationsCount = Curation::whereHas('phenotypes', function ($q) {
            $q->where('phenotypes.obsolete', true);
        })->count();

        $obsoleteUsedCount = Phenotype::query()
            ->join('curation_phenotype', 'curation_phenotype.phenotype_id', '=', 'phenotypes.id')
            ->where('phenotypes.obsolete', true)
            ->distinct('phenotypes.id')
            ->count('phenotypes.id');

        $obsoletePhenotypes = null;
        $affectedCurations = null;
        $curationGeneMap = collect();
        $obsoleteUsedPhenotypes = null;

        if ($tab === 'phenotypes') {
            $obsoletePhenotypes = Phenotype::query()
                ->select('phenotypes.*')
                ->leftJoin('curation_phenotype', 'curation_phenotype.phenotype_id', '=', 'phenotypes.id')
                ->where('phenotypes.obsolete', true)
                ->groupBy('phenotypes.id')
                ->selectRaw('COUNT(DISTINCT curation_phenotype.curation_id) AS affected_curations')
                ->selectRaw('(SELECT GROUP_CONCAT(t.curation_id ORDER BY t.curation_id SEPARATOR ",")
                    FROM (
                        SELECT DISTINCT cp.curation_id
                        FROM curation_phenotype cp
                        WHERE cp.phenotype_id = phenotypes.id
                        ORDER BY cp.curation_id
                    ) t
                ) AS curation_ids_sample')
                ->orderByDesc('affected_curations')
                ->paginate($perPage);
            
            $sampleIds = collect($obsoletePhenotypes->items())->flatMap(function ($p) {
                    return $p->curation_ids_sample ? explode(',', $p->curation_ids_sample) : [];
                })->filter()->unique()->values();

            if ($sampleIds->count() > 0) {
                $curationGeneMap = Curation::query()->whereIn('id', $sampleIds)->pluck('gene_symbol', 'id');
            }
        }

        if ($tab === 'curations') {
            $affectedCurations = Curation::query()
                ->whereHas('phenotypes', fn ($q) => $q->where('phenotypes.obsolete', true))
                ->with([
                    'expertPanel',
                    'phenotypes' => fn ($q) => $q->where('phenotypes.obsolete', true),
                ])
                ->orderBy('curations.gene_symbol')
                ->paginate($perPage);
        }

        return view(backpack_view('reports.omim_obsolete_phenotypes'), compact(
            'tab',
            'obsoletePhenotypesCount',
            'affectedCurationsCount',
            'obsoleteUsedCount',
            'obsoletePhenotypes',
            'affectedCurations',
            'obsoleteUsedPhenotypes',
            'curationGeneMap'
        ));
    }

    private function downloadCsv($request, string $tab)
    {
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => 'attachment; filename="omim_obsolete_'.$tab.'_'.now()->format('Ymd_His').'.csv"',
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0",
        ];

        $callback = function () use ($tab, $request) {
            $file = fopen('php://output', 'w');

            if ($tab === 'curations') {

                fputcsv($file, [
                    'Precuration ID',
                    'Curation Link',
                    'Gene',
                    'MONDO ID',
                    'Expert Panel',
                    'Obsolete Phenotypes',
                ]);

                $query = Curation::query()
                    ->whereHas('phenotypes', function ($q) {
                        $q->where('phenotypes.obsolete', true);
                    })
                    ->with([
                        'expertPanel',
                        'phenotypes' => fn ($q) => $q->where('phenotypes.obsolete', true),
                    ])
                    ->orderBy('id');

                $query->chunkById(200, function ($curations) use ($file) {
                    foreach ($curations as $curation) {
                        $obsoleteList = $curation->phenotypes->map(fn ($phenotype) => $phenotype->name.' ('.$phenotype->mim_number.')')->join('; ');
                        fputcsv($file, [
                            $curation->id,
                            url('home#/curations/'.$curation->id),
                            $curation->gene_symbol,
                            $curation->mondo_id,
                            optional($curation->expertPanel)->name,
                            $obsoleteList,
                        ]);
                    }
                });

                fclose($file);
                return;
            }

            fputcsv($file, [
                'Phenotype ID',
                'MIM Number',
                'Name',
                'Affected Curations Count',
                'Sample Curation Links',
            ]);

            $query = Phenotype::query()
                ->select('phenotypes.*')
                ->leftJoin('curation_phenotype', 'curation_phenotype.phenotype_id', '=', 'phenotypes.id')
                ->where('phenotypes.obsolete', true)
                ->groupBy('phenotypes.id')
                ->selectRaw('COUNT(DISTINCT curation_phenotype.curation_id) AS affected_curations')
                ->selectRaw('(
                    SELECT GROUP_CONCAT(t.curation_id ORDER BY t.curation_id SEPARATOR ",")
                    FROM (
                        SELECT DISTINCT cp.curation_id
                        FROM curation_phenotype cp
                        WHERE cp.phenotype_id = phenotypes.id
                        ORDER BY cp.curation_id
                        LIMIT 10
                    ) t
                ) AS curation_ids_sample')
                ->orderByDesc('affected_curations')
                ->orderBy('phenotypes.id');

            $query->chunk(200, function ($rows) use ($file) {
                foreach ($rows as $p) {
                    $ids = $p->curation_ids_sample ? explode(',', $p->curation_ids_sample) : [];
                    $links = collect($ids)
                        ->map(fn ($cid) => url('home#/curations/'.$cid))
                        ->join(' ');

                    fputcsv($file, [
                        $p->id,
                        $p->mim_number,
                        $p->name,
                        $p->affected_curations,
                        $links,
                    ]);
                }
            });

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }
}