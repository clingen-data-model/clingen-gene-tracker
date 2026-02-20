<?php

namespace App\Http\Controllers\Admin;

use App\Curation;
use App\Phenotype;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class OmimObsoletePhenotypeReportController extends Controller
{
    public function index(Request $request)
{
    $tab = $request->get('tab', 'phenotypes'); // phenotypes || curations ||used
    $perPage = $request->get('per_page', 20);

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
        'obsoleteUsedPhenotypes'
    ));
}

    public function oldIndex(Request $request)
    {
        // Graph 1: total obsolete phenotypes
        $obsoletePhenotypesCount = Phenotype::where('obsolete', true)->count();

        // Graph 2: affected curations (curations that have >=1 obsolete phenotype)
        $affectedCurationsCount = Curation::whereHas('phenotypes', function ($q) {
            $q->where('phenotypes.obsolete', true);
        })->count();

        // Graph 3: distinct obsolete phenotypes that are actually used on curations
        $obsoleteUsedCount = Phenotype::query()
            ->join('curation_phenotype', 'curation_phenotype.phenotype_id', '=', 'phenotypes.id')
            ->where('phenotypes.obsolete', true)
            ->distinct('phenotypes.id')
            ->count('phenotypes.id');

        // Drill list A: obsolete phenotypes, with count of affected curations
        $obsoletePhenotypes = Phenotype::query()
            ->select('phenotypes.*')
            ->leftJoin('curation_phenotype', 'curation_phenotype.phenotype_id', '=', 'phenotypes.id')
            ->where('phenotypes.obsolete', true)
            ->groupBy('phenotypes.id')
            ->selectRaw('COUNT(DISTINCT curation_phenotype.curation_id) AS affected_curations')
            ->orderByDesc('affected_curations')
            ->paginate(50);

        // Drill list B: affected curations (show only if you want a second tab)
        $affectedCurations = Curation::query()
            ->whereHas('phenotypes', fn ($q) => $q->where('phenotypes.obsolete', true))
            ->with(['expertPanel', 'phenotypes' => fn ($q) => $q->where('phenotypes.obsolete', true)])
            ->orderByDesc('updated_at')
            ->paginate(50);

        return view(backpack_view('reports.omim_obsolete_phenotypes'), compact(
            'obsoletePhenotypesCount',
            'affectedCurationsCount',
            'obsoleteUsedCount',
            'obsoletePhenotypes',
            'affectedCurations'
        ));
    }
}