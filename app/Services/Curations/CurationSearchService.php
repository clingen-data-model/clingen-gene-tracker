<?php
namespace App\Services\Curations;

use App\User;
use App\Curation;
use App\Contracts\SearchService;
use Illuminate\Support\Collection;

class CurationSearchService implements SearchService
{
    protected $validFilters = [
        'gene_symbol',
        'expert_panel_id',
        'curator_id',
        'phenotype',
        'mondo_id'
    ];

    public function search($params)
    {
        $pageSize = (isset($params['perPage']) && !is_null($params['perPage'])) ? $params['perPage'] : 25;

        $query = Curation::with('curationStatuses', 'rationales', 'curator', 'expertPanel')
                    ->select('curations.*')
                    ->join('expert_panels', 'curations.expert_panel_id', '=', 'expert_panels.id')
                    ->leftJoin('users', 'curations.curator_id', '=', 'users.id')
                    ;

        foreach ($params as $key => $value) {
            if ($key == 'with') {
                $query->with($value);
            }
            if (in_array($key, $this->validFilters)) {
                $values = array_map(function ($item) {
                    return trim($item);
                }, preg_split("/,|\n| /", $value));
                $query->whereIn($key, array_filter($values));
            }

            if ($key == 'user_id') {
                $user = User::find($value);
                if (!$user->hasRole('programmer|admin')) {
                    $query->where(function ($q) use ($user) {
                        $editorPanels = $user->coordinatorOrEditorPanels;
                        $q->where('curator_id', $user->id)
                            ->orWhereIn('expert_panel_id', $editorPanels->pluck('id'));
                    });
                }
            }
        }
        $sortField = 'gene_symbol';
        $sortDir = 'asc';

        if (isset($params['filter'])) {
            $query->where('gene_symbol', 'like', '%'.$params['filter'].'%')
                ->orWhere('expert_panels.name', 'like', '%'.$params['filter'].'%')
                ->orWhere('users.name', 'like', '%'.$params['filter'].'%')
                ->orWhere('hgnc_id', $params['filter'])
                ->orWhere('mondo_id', $params['filter'])
                ->orWhereHas('phenotypes', function ($q) use ($params) {
                    $q->where('mim_number', $params['filter']);
                })
                ;
        }

        if (isset($params['sortBy'])) {
            $sortField = $params['sortBy'];
            if ($sortField == 'expert_panel') {
                $sortField = 'expert_panels.name';
            }
            // if ($sortField == 'status') {
            //     $sortField = 'curation_statuses.name';
            // }
            if ($sortField == 'curator') {
                $sortField = 'users.name';
            }
            if ($params['sortDesc'] === 'true') {
                $sortDir = 'desc';
            }
        }
        $query->orderBy($sortField, $sortDir);

        return (isset($params['page'])) ? $query->paginate($pageSize) : $query->get();
    }
}
