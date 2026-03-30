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
    ];

    public function search($params)
    {
        $pageSize = (isset($params['perPage']) && !is_null($params['perPage'])) ? $params['perPage'] : 25;
        $query = $this->buildQuery($params);

        return isset($params['page']) ? $query->paginate($pageSize) : $query->get();
    }

    public function buildQuery($params)
    {
        $query = Curation::with(
                'currentStatus',
                'curationStatuses',
                'rationales',
                'curator',
                'expertPanel',
                'modeOfInheritance',
                'phenotypes',
                'gene.phenotypes',
                'curationType'
            )
            ->select('curations.*')
            ->join('expert_panels', 'curations.expert_panel_id', '=', 'expert_panels.id')
            ->leftJoin('users', 'curations.curator_id', '=', 'users.id')
            ->leftJoin('mode_of_inheritances', 'mode_of_inheritances.id', '=', 'curations.moi_id')
            ->leftJoin('diseases', 'diseases.mondo_id', '=', 'curations.mondo_id');

        foreach ($params as $key => $value) {
            if ($key === 'with') {
                $query->with($value);
            }

            if (in_array($key, $this->validFilters, true)) {
                if ($key === 'mondo_id') {
                    $key = 'curations.mondo_id';
                }
                if ($key === 'mondo_name') {
                    $key = 'diseases.mondo_name';
                }

                $values = array_map(function ($item) {
                    return trim($item);
                }, preg_split("/,|\n| /", $value));
                $query->whereIn($key, array_filter($values));
            }

            if ($key === 'user_id') {
                $user = User::find($value);
                if ($user && !$user->hasRole('programmer|admin')) {
                    $query->where(function ($q) use ($user) {
                        $editorPanels = $user->coordinatorOrEditorPanels;
                        $q->where('curations.curator_id', $user->id)
                          ->orWhereIn('curations.expert_panel_id', $editorPanels->pluck('id'));
                    });
                }
            }
        }

        $this->applyKeywordFilter($query, $params);
        $this->applyAdvancedFilters($query, $params);

        $sortField = 'gene_symbol';
        $sortDir = 'asc';

        if (isset($params['sortBy'])) {
            $sortField = $params['sortBy'];
            if ($sortField === 'expert_panel') {
                $sortField = 'expert_panels.name';
            }
            if ($sortField === 'mode_of_inheritance') {
                $sortField = 'mode_of_inheritances.name';
            }
            if ($sortField === 'curator') {
                $sortField = 'users.name';
            }
            if ($sortField === 'mondo_id') {
                $sortField = 'diseases.mondo_id';
            }
            if ($sortField === 'id') {
                $sortField = 'curations.id';
            }
            if ($params['sortDesc'] === 'true') {
                $sortDir = 'desc';
            }
        }
        $query->orderBy($sortField, $sortDir);

        return $query;
    }

    private function applyKeywordFilter($query, $params)
    {
        if (!isset($params['filter']) || trim((string) $params['filter']) === '') {
            return;
        }
        $filter = $this->escapeLike($params['filter']);
        $query->where(function ($q) use ($filter) {
            $q->where('curations.gene_symbol', 'like', '%' . $filter . '%')
                ->orWhere('expert_panels.name', 'like', '%' . $filter . '%')
                ->orWhere('users.name', 'like', '%' . $filter . '%')
                ->orWhere('curations.hgnc_name', 'like', '%' . $filter . '%')
                ->orWhere('curations.mondo_id', 'like', '%' . $filter . '%')
                ->orWhere('diseases.name', 'like', '%' . $filter . '%')
                ->orWhereHas('phenotypes', function ($pq) use ($filter) {
                    $pq->where('mim_number', $filter);
                })
                ->orWhereHas('modeOfInheritance', function ($mq) use ($filter) {
                    $mq->where('abbreviation', 'like', '%' . $filter . '%')->orWhere('name', 'like', '%' . $filter . '%');
                })
                ->orWhereHas('currentStatus', function ($sq) use ($filter) {
                    $sq->where('name', 'like', '%' . $filter . '%');
                });

            if (is_numeric($filter)) {
                $q->orWhere('curations.id', (int) $filter)
                  ->orWhere('curations.hgnc_id', (int) $filter);
            }

            if (preg_match('/hgnc:/i', $filter)) {
                $hgncId = substr($filter, 5);
                $q->orWhere('curations.hgnc_id', $hgncId);
            }
        });
    }

    private function applyAdvancedFilters($query, $params)
    {
        $filters = $params['filters'] ?? [];

        if (is_string($filters)) {
            $decoded = json_decode($filters, true);
            $filters = is_array($decoded) ? $decoded : [];
        }

        if (!is_array($filters)) {
            return;
        }

        foreach ($filters as $field => $value) {
            $escapedValue = $this->escapeLike($value);            
            if ($value === null || $value === '') {
                continue;
            }
            $this->applyFieldFilter($query, $field, $value);
        }
    }


    private function parseList($value): array
    {
        $values = array_map(fn ($v) => trim($v), preg_split('/,|\s+|\n/', (string) $value));
        return array_values(array_filter($values, fn ($v) => $v !== ''));
    }

    private function applyFieldFilter($query, $field, $value)
    {
        switch ($field) {
            case 'uuid':
                $query->where('curations.uuid', $value);
                break;

            case 'gene_symbol':
                $query->where('curations.gene_symbol', 'like', '%' . $value . '%');
                break;

            case 'expert_panel':
                $query->where('expert_panels.name', 'like', '%' . $value . '%');
                break;

            case 'curator':
                $query->where('users.name', 'like', '%' . $value . '%');
                break;

            case 'mode_of_inheritance':
                $query->whereHas('modeOfInheritance', function ($q) use ($value) {
                    $q->where('abbreviation', 'like', '%' . $value . '%')
                    ->orWhere('name', 'like', '%' . $value . '%');
                });
                break;

            case 'mondo_id':
                $query->where(function ($q) use ($value) {
                    $q->where('curations.mondo_id', 'like', '%' . $value . '%')
                    ->orWhere('diseases.name', 'like', '%' . $value . '%');
                });
                break;

            case 'id':
                if (is_numeric($value)) {
                    $query->where('curations.id', (int) $value);
                } else {
                    $query->whereRaw('CAST(curations.id AS CHAR) like ?', ['%' . $value . '%']);
                }
                break;

            case 'current_status':
                $query->whereHas('currentStatus', function ($q) use ($value) {
                    if (is_numeric($value)) {
                        $q->where('id', (int) $value);
                    } else {
                        $q->where('name', $value);
                    }
                });
                break;

            default:
                throw new Exception('Unknown advanced filter ' . $field);
        }
    }

    private function escapeLike(string $value): string
    {
        return addcslashes(trim((string) $value), '\\%_');
    }
}