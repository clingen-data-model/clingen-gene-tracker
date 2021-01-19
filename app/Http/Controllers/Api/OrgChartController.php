<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\WorkingGroup;

class OrgChartController extends Controller
{
    public function index()
    {
        // $cdwgs = WorkingGroup::select('id', 'name')
        //             ->with([
        //                 'expertPanels' => function ($q) {
        //                     $q->select('id', 'name', 'working_group_id', 'affiliation_id');
        //                 },
        //                 'expertPanels.affiliation' => function ($q) {
        //                     $q->select('name', 'clingen_id', 'parent_id', 'id');
        //                 },
        //                 'expertPanels.affiliation.parent' => function ($q) {
        //                     $q->select('name', 'clingen_id', 'parent_id', 'id');
        //                 }
        //             ])
        //             ->get();

        $results = \DB::select('select wg.name as cdwg, ep.id as ep_id, ep.name as \'ep_name\', a.clingen_id as affiliation_subgroup_id, ap.clingen_id as affiliation_id, a.name as affiliation_subgroup_name, ap.name as affiliation_name
        from  expert_panels ep
            left join working_groups wg on ep.working_group_id = wg.id
            left join affiliations a on ep.affiliation_id = a.id
            left join affiliations ap on a.parent_id = ap.id
        order by cdwg');
        // return $results;

        // $cdwgs = collect($results)->groupBy('cdwg')->values();
        $cdwgs = [];
        foreach ($results as $result) {
            $name = is_null($result->cdwg) ? 'No CDWG' : $result->cdwg;
            if (!isset($cdwgs[$name])) {
                $cdwgs[$name]['expert_panels'] = [];
            }
            $cdwgs[$name]['expert_panels'][] = [
                'id' => $result->ep_id,
                'name' => $result->ep_name,
                'affiliation_id' => $result->affiliation_id,
                'affiliation_name' => $result->affiliation_name,
                'affiliation_subgroup_id' => $result->affiliation_subgroup_id,
                'affiliation_subgroup_name' => $result->affiliation_subgroup_name,
            ];
        }

        return $cdwgs;
    }
}
