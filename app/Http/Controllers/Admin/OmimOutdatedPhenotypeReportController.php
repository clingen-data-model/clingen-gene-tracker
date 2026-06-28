<?php

namespace App\Http\Controllers\Admin;

use App\Curation;
use App\Phenotype;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Response;

class OmimOutdatedPhenotypeReportController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'phenotypes'); // phenotypes || curations
        if (!in_array($tab, ['phenotypes', 'curations'], true)) {
            $tab = 'phenotypes';
        }
        $perPage = max(1, min((int) $request->get('per_page', 20), 100));

        if ($request->get('download') === 'csv') {
            return $this->downloadCsv($tab);
        }

        $outdatedPhenotypesCount = Phenotype::whereNotNull('label_obsolete_at')->count();

        $affectedCurationsCount = Curation::whereHas('phenotypes', function ($q) {
            $q->whereNotNull('phenotypes.label_obsolete_at');
        })->count();

        $outdatedUsedCount = Phenotype::query()
            ->join('curation_phenotype', 'curation_phenotype.phenotype_id', '=', 'phenotypes.id')
            ->whereNotNull('phenotypes.label_obsolete_at')
            ->distinct()
            ->count('phenotypes.id');

        $outdatedPhenotypes = null;
        $affectedCurations = null;
        $curationGeneMap = collect();

        if ($tab === 'phenotypes') {
            $outdatedPhenotypes = Phenotype::query()
                ->select('phenotypes.*')
                ->leftJoin('curation_phenotype', 'curation_phenotype.phenotype_id', '=', 'phenotypes.id')
                ->whereNotNull('phenotypes.label_obsolete_at')
                ->groupBy('phenotypes.id')
                ->selectRaw('COUNT(DISTINCT curation_phenotype.curation_id) AS affected_curations')
                ->selectRaw(
                    'GROUP_CONCAT(
                        DISTINCT curation_phenotype.curation_id
                        ORDER BY curation_phenotype.curation_id
                        SEPARATOR ","
                    ) AS curation_ids_sample'
                )
                ->orderByDesc('affected_curations')
                ->paginate($perPage);
            
            $sampleIds = collect($outdatedPhenotypes->items())->flatMap(function ($p) {
                    return $p->curation_ids_sample ? explode(',', $p->curation_ids_sample) : [];
                })->filter()->unique()->values();

            if ($sampleIds->count() > 0) {
                $curationGeneMap = Curation::query()->whereIn('id', $sampleIds)->pluck('gene_symbol', 'id');
            }
        }

        if ($tab === 'curations') {
            $affectedCurations = Curation::query()
                ->whereHas('phenotypes', fn ($q) => $q->whereNotNull('phenotypes.label_obsolete_at'))
                ->with([
                    'expertPanel',
                    'phenotypes' => fn ($q) => $q->whereNotNull('phenotypes.label_obsolete_at'),
                ])
                ->orderBy('curations.gene_symbol')
                ->paginate($perPage);
        }

        return view(backpack_view('reports.omim_outdated_phenotypes'), compact(
            'tab',
            'outdatedPhenotypesCount',
            'affectedCurationsCount',
            'outdatedUsedCount',
            'outdatedPhenotypes',
            'affectedCurations',
            'curationGeneMap'
        ));
    }

    private function downloadCsv(string $tab)
    {
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => 'attachment; filename="omim_outdated_phenotype_labels_'.$tab.'_'.now()->format('Ymd_His').'.csv"',
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0",
        ];

        $callback = function () use ($tab) {
            $file = fopen('php://output', 'w');

            if ($tab === 'curations') {

                fputcsv($file, [
                    'Precuration ID',
                    'Curation Link',
                    'Gene',
                    'MONDO ID',
                    'Expert Panel',
                    'Outdated Phenotype Labels',
                ]);

                $query = Curation::query()
                    ->whereHas('phenotypes', function ($q) {
                        $q->whereNotNull('phenotypes.label_obsolete_at');
                    })
                    ->with([
                        'expertPanel',
                        'phenotypes' => fn ($q) => $q->whereNotNull('phenotypes.label_obsolete_at'),
                    ])
                    ->orderBy('id');

                $query->chunkById(200, function ($curations) use ($file) {
                    foreach ($curations as $curation) {
                        $outdatedList = $curation->phenotypes->map(fn ($phenotype) => $phenotype->name.' ('.$phenotype->mim_number.')')->join('; ');
                        fputcsv($file, [
                            $curation->id,
                            url('home#/curations/'.$curation->id),
                            $curation->gene_symbol,
                            $curation->mondo_id,
                            optional($curation->expertPanel)->name,
                            $outdatedList,
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
                ->whereNotNull('phenotypes.label_obsolete_at')
                ->groupBy('phenotypes.id')
                ->selectRaw('COUNT(DISTINCT curation_phenotype.curation_id) AS affected_curations')
                ->selectRaw('GROUP_CONCAT(
                        DISTINCT curation_phenotype.curation_id
                        ORDER BY curation_phenotype.curation_id
                        SEPARATOR ","
                    ) AS curation_ids_sample'
                )
                ->orderByDesc('affected_curations')
                ->orderBy('phenotypes.id');

            $query->chunk(200, function ($rows) use ($file) {
                foreach ($rows as $p) {
                    $ids = collect($p->curation_ids_sample ? explode(',', $p->curation_ids_sample) : [])->take(10);
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