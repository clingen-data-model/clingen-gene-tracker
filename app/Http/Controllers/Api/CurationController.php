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
use App\Phenotype;
use App\Services\Curations\CurationSearchService;
use App\Services\RequestDataCleaner;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CurationController extends Controller
{
    protected $cleaner;
    protected $omim;
    protected $search;

    public function __construct(RequestDataCleaner $cleaner, OmimClient $omim, CurationSearchService $search)
    {
        $this->cleaner = $cleaner;
        $this->omim = $omim;
        // $this->middleware('role:programmer|admin')->only(['destroy']);
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
                SyncPhenotypes::dispatchSync($curation, $request->phenotypes);
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
        $curation = Curation::findByAnyId($id);
        
        if (!$curation) {
            return response('Curation not found', 404);
        }

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

        // Ignore data that should not be manually updated.
        $data = $request->except(['curation_status_id', 'hgnc_id', 'hgnc_name']);
        $data = $this->setMondoId($data);

        try {
            $curation->update($data);
            $this->setPhenotypes($curation, $request);
            $this->setRationales($curation, $request->rationales);
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
        $this->authorize('delete', $curation);
        $curation->delete();

        return response()->json(['message' => 'You successfully deleted curation with id '.$id]);
    }

    private function setPhenotypes(Curation $curation, $request)
    {
        if ($request->phenotypes) {
            $phenotypeIds = array_map(fn($el) => $el['id'], $request->phenotypes);
            SyncPhenotypes::dispatchSync($curation, $phenotypeIds);
        }

        if ($request->isolated_phenotype) {
            // $pheno = $this->omim->getEntry($request->isolated_phenotype);
            $pheno = Phenotype::findByMimNumber($request->isolated_phenotype);
            SyncPhenotypes::dispatchSync($curation, [$pheno->id]);
        }
    }

    private function setRationales(Curation $curation, $rationales)
    {
        if ($rationales) {
            $curation->rationales()->sync(collect($rationales)->pluck('id'));
        }
    }
    
    private function setMondoId($data)
    {
        if (isset($data['disease']) && is_array($data['disease'])) {
            $data['mondo_id'] = $data['disease']['mondo_id'];
        }
        return $data;
    }
    

    private function loadRelations(&$curation)
    {
        $curation->load(['phenotypes', 'expertPanel', 'expertPanel.affiliation', 'expertPanels', 'curator', 'curationStatuses', 'rationales', 'curationType', 'classifications', 'modeOfInheritance', 'disease', 'notes', 'notes.author']);
    }
}
