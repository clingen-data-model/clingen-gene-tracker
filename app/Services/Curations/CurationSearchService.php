<?php

namespace App\Services\Curations;

use App\User;
use Exception;
use App\Curation;
use App\Contracts\SearchService;
use Illuminate\Support\Facades\DB;

class CurationSearchService implements SearchService
{
    protected $validFilters = [
        'gene_symbol',
        'expert_panel_id',
        'curator_id',
        'phenotype',
        'mondo_id',
        'curation.mondo_id',
        'diseases.mondo_id',
        'mondo_name',
        'hgnc_id',
        'hgnc_name',
        'moi_id',
        
        'curations.uuid', // USED BY GPM VCEP APPLICATION
        'uuid', // optional alias
        'phenotypes.mim_number', // USED BY GPM VCEP/GCEP APPLICATION REVISION MODE
        // 'gene.phenotypes.mim_number', // CONFUSING THING. CURATION->PHENOTYPE == CURATION->GENE->PHENOTYPE 
    ];

    public function search($params)
    {
        $pageSize = (isset($params['perPage']) && !is_null($params['perPage'])) ? $params['perPage'] : 25;
        $query = $this->buildQuery($params);

        $data = (isset($params['page'])) ? $query->paginate($pageSize) : $query->get();

        return $data;
    }

    public function buildQuery($params)
    {
        $query = Curation::with('curationStatuses','rationales','curator','expertPanel','modeOfInheritance','phenotypes','gene.phenotypes','curationType')        
                        ->select('curations.*')
                        ->distinct()
                        ->join('expert_panels', 'curations.expert_panel_id', '=', 'expert_panels.id')
                        ->leftJoin('users', 'curations.curator_id', '=', 'users.id')
                        ->leftJoin('mode_of_inheritances', 'mode_of_inheritances.id', '=', 'curations.moi_id')
                        ->leftJoin('diseases', 'diseases.mondo_id', '=', 'curations.mondo_id');

        foreach ($params as $key => $value) {
            if ($key === 'with') { $query->with($value); }

            // 1) classifications.uuid
            if ($key === 'uuid') {
                $key = 'curations.uuid';
            }

            // 2) curation phenotypes: phenotypes.mim_number
            if ($key === 'phenotypes.mim_number') {
                $mims = array_map(fn ($v) => is_numeric($v) ? (int) $v : $v, $this->parseList($value));
                if ($mims) {
                    $query->whereHas('phenotypes', function ($q) use ($mims) {
                        $q->whereIn('mim_number', $mims);
                    });
                }
                continue;
            }

            // Existing simple column filters
            if (in_array($key, $this->validFilters, true)) {
                if ($key === 'mondo_id')   { $key = 'curations.mondo_id'; }
                if ($key === 'mondo_name') { $key = 'diseases.name'; }

                $values = $this->parseList($value);
                if ($values) {
                    $query->whereIn($key, $values);
                }
            }

            // Existing user filter
            if ($key === 'user_id') {
                $user = User::find($value);
                if ($user && !$user->hasRole('programmer|admin')) {
                    $query->where(function ($q) use ($user) {
                        $editorPanels = $user->coordinatorOrEditorPanels;
                        $q->where('curator_id', $user->id)
                        ->orWhereIn('expert_panel_id', $editorPanels->pluck('id'));
                    });
                }
            }
        }

        if (!empty($params['same_mims_as_classification'])) {
            $uuid = trim($params['same_mims_as_classification']);

            // Pull MIM set from curations that have this classification
            $mims = DB::table('phenotypes')
                ->join('curation_phenotype', 'phenotypes.id', '=', 'curation_phenotype.phenotype_id')
                ->join('curations', 'curations.id', '=', 'curation_phenotype.curation_id')
                ->where('curations.uuid', $uuid)
                ->pluck('phenotypes.mim_number')
                ->unique()
                ->filter()
                ->values()
                ->all();

            if (!empty($mims)) {
                $query->whereHas('phenotypes', function ($q) use ($mims) {
                    $q->whereIn('mim_number', $mims);
                });
            } else {
                $query->whereRaw('1=0'); // no matches when no MIMs
            }
        }


        $this->applyFilter($query, $params);

        $sortField = 'gene_symbol';
        $sortDir = 'asc';


        if (isset($params['sortBy'])) {
            $sortField = $params['sortBy'];
            if ($sortField == 'expert_panel') {
                $sortField = 'expert_panels.name';
            }
            if ($sortField == 'mode_of_inheritance') {
                $sortField = 'mode_of_inheritances.name';
            }
            if ($sortField == 'curator') {
                $sortField = 'users.name';
            }
            if ($params['sortDesc'] === 'true') {
                $sortDir = 'desc';
            }
        }
        $query->orderBy($sortField, $sortDir);

        // \Log::debug(renderQuery($query));
        return $query;
    }

    private function applyFilter($query, $params)
    {
        if (!isset($params['filter'])) {
            return $query;
        }

        $filter = $params['filter'];

        // Field-specific filter
        if (isset($params['filter_field'])) {
            switch ($params['filter_field']) {
                case 'gene_symbol':
                    return $query->where('gene_symbol', 'like', "%{$filter}%");

                case 'expert_panel':
                    return $query->where('expert_panels.name', 'like', "%{$filter}%");

                case 'mode_of_inheritance':
                    return $query->whereHas('modeOfInheritance', function ($q) use ($filter) {
                        $q->where(function ($qq) use ($filter) {
                            $qq->where('abbreviation', 'like', "%{$filter}%")
                            ->orWhere('name', 'like', "%{$filter}%");
                        });
                    });

                case 'mondo_id':
                    // group the ORs
                    return $query->where(function ($q) use ($filter) {
                        $q->where('curations.mondo_id', 'like', "%{$filter}%")
                        ->orWhere('diseases.name', 'like', "%{$filter}%");
                    });

                default:
                    throw new Exception('Unkown filter_field '.$params['filter_field']);
            }
        }

        // Free-text filter: group ALL ORs
        $hgncPrefixed = null;
        if (preg_match('/^hgnc:\s*\d+$/i', $filter)) {
            $hgncPrefixed = trim(substr($filter, 5));
        }

        return $query->where(function ($q) use ($filter, $hgncPrefixed) {
            $q->where('gene_symbol', 'like', "%{$filter}%")
            ->orWhere('expert_panels.name', 'like', "%{$filter}%")
            ->orWhere('users.name', 'like', "%{$filter}%")            
            ->orWhere('hgnc_name', 'like', "%{$filter}%")
            ->orWhere('curations.mondo_id', 'like', "%{$filter}%")
            ->orWhere('diseases.name', 'like', "%{$filter}%")
            ->orWhereHas('phenotypes', function ($r) use ($filter) {
                $r->where('mim_number', $filter);
            });
            if ($hgncPrefixed) {
                $q->orWhere('hgnc_id', $hgncPrefixed);
            }
        });
    }


    private function parseList($value): array
    {
        $values = array_map(fn ($v) => trim($v), preg_split('/,|\s+|\n/', (string) $value));
        return array_values(array_filter($values, fn ($v) => $v !== ''));
    }
    
}
