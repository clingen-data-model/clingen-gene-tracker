<?php

namespace App\Http\Controllers\Api;

use App\User;
use App\Curation;
use Carbon\Carbon;
use App\CurationStatus;
use Illuminate\Http\Request;
use App\Contracts\OmimClient;
use App\Jobs\Curations\AddStatus;
use App\Http\Controllers\Controller;
use App\Services\RequestDataCleaner;
use App\Jobs\Curations\SyncPhenotypes;
use App\Http\Resources\CurationResource;
use App\Http\Requests\CurationCreateRequest;
use App\Http\Requests\CurationUpdateRequest;

class CurationController extends Controller
{
    protected $cleaner;
    protected $omim;

    protected $validFilters = [
        'gene_symbol',
        'expert_panel_id',
        'curator_id',
        'phenotype',
        'mondo_id'
    ];

    public function __construct(RequestDataCleaner $cleaner, OmimClient $omim)
    {
        $this->cleaner = $cleaner;
        $this->omim = $omim;
        $this->middleware('role:programmer|admin')->only(['destroy']);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $pageSize = ($request->has('perPage') && !is_null($request->perPage)) ? $request->perPage : 25;

        $query = Curation::with('curationStatuses', 'rationales', 'curator', 'expertPanel')
                    ->select('curations.*')
                    ->join('expert_panels', 'curations.expert_panel_id', '=', 'expert_panels.id')
                    ->leftJoin('users', 'curations.curator_id', '=', 'users.id')
                    // ->join('curation_statuses', 'curations.curation_status_id', '=', 'curation_statuses.id')
                    ;
        foreach ($request->all() as $key => $value) {
            if ($key == 'with') {
                $query->with($value);
            }
            if (in_array($key, $this->validFilters)) {
                $query->where($key, $value);
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

        if (!is_null($request->filter)) {
            $query->where('gene_symbol', 'like', '%'.$request->filter.'%')
                ->orWhere('expert_panels.name', 'like', '%'.$request->filter.'%')
                ->orWhere('users.name', 'like', '%'.$request->filter.'%')
                ;
        }

        if ($request->sortBy) {
            $sortField = $request->sortBy;
            if ($sortField == 'expert_panel') {
                $sortField = 'expert_panels.name';
            }
            // if ($sortField == 'status') {
            //     $sortField = 'curation_statuses.name';
            // }
            if ($sortField == 'curator') {
                $sortField = 'users.name';
            }
            if ($request->sortDesc === 'true') {
                $sortDir = 'desc';
            }
        }
        $query->orderBy($sortField, $sortDir);

        $curations = ($request->has('page')) ? $query->paginate($pageSize) : $query->get();

        return CurationResource::collection($curations);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CurationCreateRequest $request)
    {
        $this->authorize('create', Curation::class);
        $data = $request->except('phenotypes', 'curation_status_id');
        $curation = Curation::create($data);
        if ($request->phenotypes) {
            SyncPhenotypes::dispatchNow($curation, $request->phenotypes);
        }
        if ($request->curation_status_id) {
            AddStatus::dispatch($curation, CurationStatus::find($request->curation_status_id));
        }
        $this->loadRelations($curation);

        return new CurationResource($curation);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $curation = Curation::findOrFail($id);
        $this->loadRelations($curation);
        $curation->rationals = $curation->rationales->transform(function ($item) {
            unset($item->pivot);

            return $item;
        });

        return new CurationResource($curation);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(CurationUpdateRequest $request, $id)
    {
        $curation = Curation::findOrFail($id);
        $this->authorize('update', $curation);

        $data = $request->except('curation_status_id');
        
        if (isset($data['pmids']) && is_string($data['pmids'])) {
            $data['pmids'] = array_map(function ($i) {
                return trim($i);
            }, explode(',', $data['pmids']));
        }

        $curation->update($data);

        if ($request->phenotypes) {
            SyncPhenotypes::dispatchNow($curation, $request->phenotypes);
        }

        if ($request->isolated_phenotype) {
            $pheno = $this->omim->getEntry($request->isolated_phenotype);
            SyncPhenotypes::dispatchNow($curation, [
                ['mim_number'=>$pheno->mimNumber, 'name'=> $pheno->titles->preferredTitle]
            ]);
        }
        
        if ($request->rationales) {
            $curation->rationales()->sync(collect($request->rationales)->pluck('id'));
        }

        if ($request->curation_status_id) {
            $status_date = ($request->curation_status_timestamp) ? Carbon::parse($request->curation_status_timestamp) : now();
            $curation->curationStatuses()->attach([
                $request->curation_status_id => [
                    'status_date' => $status_date
                ]
            ]);
        }

        $this->loadRelations($curation);

        return new CurationResource($curation);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $curation = Curation::findOrFail($id);
        $curation->delete();
        
        return response()->json(['message' => 'You successfully deleted curation with id '.$id]);
    }

    private function loadRelations(&$curation)
    {
        $curation->load(['phenotypes', 'expertPanel', 'curator', 'curationStatuses', 'rationales', 'curationType', 'classifications']);
    }
}
