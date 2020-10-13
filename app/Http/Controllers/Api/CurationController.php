<?php

namespace App\Http\Controllers\Api;

use App\Contracts\OmimClient;
use App\Curation;
use App\CurationStatus;
use App\Exceptions\ApiServerErrorException;
use App\Http\Controllers\Controller;
use App\Http\Requests\CurationCreateRequest;
use App\Http\Requests\CurationUpdateRequest;
use App\Http\Resources\CurationResource;
use App\Jobs\Curations\AddStatus;
use App\Jobs\Curations\SyncPhenotypes;
use App\Services\Curations\CurationSearchService;
use App\Services\RequestDataCleaner;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CurationController extends Controller
{
    protected $cleaner;
    protected $omim;

    public function __construct(RequestDataCleaner $cleaner, OmimClient $omim, CurationSearchService $search)
    {
        $this->cleaner = $cleaner;
        $this->omim = $omim;
        $this->middleware('role:programmer|admin')->only(['destroy']);
        $this->search = $search;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $curations = $this->search->search($request->all());

        $resourceResponse = CurationResource::collection($curations);

        return $resourceResponse;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function store(CurationCreateRequest $request)
    {
        $this->authorize('create', Curation::class);
        $data = $request->except('phenotypes', 'curation_status_id', 'page');
        try {
            $curation = Curation::create($data);
            if ($request->phenotypes) {
                SyncPhenotypes::dispatchNow($curation, $request->phenotypes);
            }
            if ($request->curation_status_id) {
                AddStatus::dispatch($curation, CurationStatus::find($request->curation_status_id));
            }
        } catch (ApiServerErrorException $th) {
            report($th);
            $curation = Curation::where($data)->first();
        }

        $this->loadRelations($curation);

        return new CurationResource($curation);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     *
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
     * @param \Illuminate\Http\Request $request
     * @param int                      $id
     *
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

        try {
            $curation->update($data);

            if ($request->phenotypes) {
                SyncPhenotypes::dispatchNow($curation, $request->phenotypes);
            }

            if ($request->isolated_phenotype) {
                $pheno = $this->omim->getEntry($request->isolated_phenotype);
                SyncPhenotypes::dispatchNow($curation, [
                    [
                        'mim_number' => $pheno->mimNumber,
                        'name' => $pheno->titles->preferredTitle,
                    ],
                ]);
            }

            if ($request->rationales) {
                $curation->rationales()->sync(collect($request->rationales)->pluck('id'));
            }

            if ($request->curation_status_id) {
                $status_date = ($request->curation_status_timestamp) ? Carbon::parse($request->curation_status_timestamp) : now();
                $curation->curationStatuses()->attach([
                    $request->curation_status_id => [
                        'status_date' => $status_date,
                    ],
                ]);
            }
        } catch (ApiServerErrorException $e) {
            report($e);
        }

        $this->loadRelations($curation);

        return new CurationResource($curation);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     *
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
        $curation->load(['phenotypes', 'expertPanel', 'curator', 'curationStatuses', 'rationales', 'curationType', 'classifications', 'modeOfInheritance']);
    }
}
