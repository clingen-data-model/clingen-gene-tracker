<?php

namespace App\Http\Controllers\Api;

use App\Curation;
use App\Http\Controllers\Controller;
use App\Phenotype;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OutdatedPhenotypeReportController extends Controller
{
    public function dashboard(Request $request): array
    {
        $this->authorizeAdministrator($request);

        return [
            'outdated_phenotypes' => Phenotype::outdatedCount(),
            'affected_curations' => Phenotype::affectedCurationsCount(),
            'outdated_phenotypes_in_use' => Phenotype::outdatedInUseCount(),
        ];
    }

    public function phenotypes(Request $request)
    {
        $this->authorizeAdministrator($request);

        $paginator = $this->phenotypeQuery()->paginate($this->perPage($request));
        $curationIds = $paginator->getCollection()
            ->flatMap(fn ($phenotype) => $phenotype->curation_ids_sample
                ? explode(',', $phenotype->curation_ids_sample)
                : [])
            ->filter()->unique()->values();
        $curations = Curation::query()
            ->whereIn('id', $curationIds)
            ->get(['id', 'gene_symbol'])
            ->keyBy('id');

        $paginator->setCollection($paginator->getCollection()->map(function ($phenotype) use ($curations) {
            return [
                'id' => $phenotype->id,
                'mim_number' => $phenotype->mim_number,
                'name' => $phenotype->name,
                'label_obsolete_at' => $phenotype->label_obsolete_at,
                'affected_curations' => (int) $phenotype->affected_curations,
                'curations' => collect($phenotype->curation_ids_sample
                    ? explode(',', $phenotype->curation_ids_sample)
                    : [])->map(fn ($id) => [
                        'id' => (int) $id,
                        'gene_symbol' => optional($curations->get($id))->gene_symbol,
                    ])->values(),
            ];
        }));

        return $paginator;
    }

    public function curations(Request $request)
    {
        $this->authorizeAdministrator($request);

        $paginator = Curation::query()
            ->whereHas('phenotypes', fn ($query) => $query->whereNotNull('phenotypes.label_obsolete_at'))
            ->with([
                'expertPanel:id,name',
                'phenotypes' => fn ($query) => $query
                    ->whereNotNull('phenotypes.label_obsolete_at')
                    ->select('phenotypes.id', 'mim_number', 'name'),
            ])
            ->orderBy('curations.gene_symbol')
            ->paginate($this->perPage($request));

        $paginator->setCollection($paginator->getCollection()->map(fn ($curation) => [
            'id' => $curation->id,
            'gene_symbol' => $curation->gene_symbol,
            'mondo_id' => $curation->mondo_id,
            'mondo_name' => $curation->mondo_name,
            'expert_panel' => $curation->expertPanel,
            'phenotypes' => $curation->phenotypes,
        ]));

        return $paginator;
    }

    public function phenotypeCsv(Request $request): StreamedResponse
    {
        $this->authorizeAdministrator($request);

        return response()->streamDownload(function () {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Phenotype ID', 'MIM Number', 'Name', 'Affected Curations Count', 'Sample Curation Links']);
            $this->phenotypeQuery()->orderBy('phenotypes.id')->chunk(200, function ($rows) use ($file) {
                foreach ($rows as $phenotype) {
                    $links = collect($phenotype->curation_ids_sample
                        ? explode(',', $phenotype->curation_ids_sample)
                        : [])->take(10)->map(fn ($id) => url('home#/curations/'.$id))->join(' ');
                    fputcsv($file, [
                        $phenotype->id,
                        $phenotype->mim_number,
                        $phenotype->name,
                        $phenotype->affected_curations,
                        $links,
                    ]);
                }
            });
            fclose($file);
        }, 'omim_outdated_phenotype_labels_phenotypes_'.now()->format('Ymd_His').'.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function curationCsv(Request $request): StreamedResponse
    {
        $this->authorizeAdministrator($request);

        return response()->streamDownload(function () {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Precuration ID', 'Curation Link', 'Gene', 'MONDO ID', 'Expert Panel', 'Outdated Phenotype Labels']);
            Curation::query()
                ->whereHas('phenotypes', fn ($query) => $query->whereNotNull('phenotypes.label_obsolete_at'))
                ->with([
                    'expertPanel:id,name',
                    'phenotypes' => fn ($query) => $query->whereNotNull('phenotypes.label_obsolete_at'),
                ])
                ->orderBy('id')
                ->chunkById(200, function ($curations) use ($file) {
                    foreach ($curations as $curation) {
                        fputcsv($file, [
                            $curation->id,
                            url('home#/curations/'.$curation->id),
                            $curation->gene_symbol,
                            $curation->mondo_id,
                            optional($curation->expertPanel)->name,
                            $curation->phenotypes
                                ->map(fn ($phenotype) => $phenotype->name.' ('.$phenotype->mim_number.')')
                                ->join('; '),
                        ]);
                    }
                });
            fclose($file);
        }, 'omim_outdated_phenotype_labels_curations_'.now()->format('Ymd_His').'.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    private function phenotypeQuery()
    {
        return Phenotype::query()
            ->select(['phenotypes.id', 'phenotypes.mim_number', 'phenotypes.name', 'phenotypes.label_obsolete_at'])
            ->leftJoin('curation_phenotype', 'curation_phenotype.phenotype_id', '=', 'phenotypes.id')
            ->whereNotNull('phenotypes.label_obsolete_at')
            ->groupBy('phenotypes.id', 'phenotypes.mim_number', 'phenotypes.name', 'phenotypes.label_obsolete_at')
            ->selectRaw('COUNT(DISTINCT curation_phenotype.curation_id) AS affected_curations')
            ->selectRaw('GROUP_CONCAT(DISTINCT curation_phenotype.curation_id ORDER BY curation_phenotype.curation_id SEPARATOR ",") AS curation_ids_sample')
            ->orderByDesc('affected_curations');
    }

    private function perPage(Request $request): int
    {
        return max(1, min((int) $request->get('per_page', 20), 100));
    }

    private function authorizeAdministrator(Request $request): void
    {
        abort_unless($request->user()->hasAnyRole(['admin', 'programmer']), 403);
    }
}
