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
        $data = $request->except('phenotypes', 'curation_status_id', 'page', 'archived_curation_ids');
        try {
            $curation = Curation::create($data);
            if ($request->phenotypes) {
                SyncPhenotypes::dispatchSync($curation, $request->phenotypes);
            }
            if ($request->curation_status_id) {
                AddStatus::dispatch($curation, CurationStatus::find($request->curation_status_id));
            }
            $this->syncArchivedCurationLinks($curation, $request->all());
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
            $this->syncArchivedCurationLinks($curation, $request->all());
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
        $curation->load(['phenotypes', 'expertPanel', 'expertPanel.affiliation', 'expertPanels', 'curator', 'curationStatuses', 'rationales', 'curationType', 'classifications', 'modeOfInheritance', 'disease', 'notes', 'notes.author', 'linkedArchivedCurations.expertPanel', 'linkedCurrentCurations.expertPanel']);
    }

    protected function syncArchivedCurationLinks(Curation $curation, array $data): void
    {
        if ($curation->is_archived) {
            return;
        }

        if (!array_key_exists('archived_curation_ids', $data)) {
            return;
        }

        $ids = collect($data['archived_curation_ids'] ?? [])
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->reject(fn ($id) => $id === (int) $curation->id)
            ->values();

        $validArchivedIds = Curation::query()
            ->whereIn('id', $ids)
            ->whereNotNull('archived_at')
            ->pluck('id')
            ->all();

        $curation->linkedArchivedCurations()->sync($validArchivedIds);
    }

    public function archive(Request $request, Curation $curation)
    {
        $this->authorize('archive', $curation);
        $data = $request->validate([
            'archive_reason' => ['nullable', 'string', 'max:2000'],
            'gcex_url' => ['nullable', 'url'],
        ]);
        $curation->update([
            'archived_at' => now(),
            'archive_reason' => $data['archive_reason'] ?? null,
            'gcex_url' => $data['gcex_url'] ?? null,
        ]);
        return response()->json($curation->fresh());
    }

    public function unarchive(Request $request, Curation $curation)
    {
        $this->authorize('unarchive', $curation);
        $curation->update([
            'archived_at' => null,
            'archive_reason' => null,
            'gcex_url' => null,
        ]);
        return response()->json($curation->fresh());
    }

    public function searchArchivedCurations(Request $request)
    {
        $search = trim($request->get('q', ''));
        $query = Curation::query()
            ->whereNotNull('archived_at');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('gene_symbol', 'like', "%{$search}%")
                    ->orWhere('id', '=', $search)
                    ->orWhere('hgnc_id', '=', $search)
                    ->orWhere('gdm_uuid', 'like', "%{$search}%");
            });
        }

        return CurationResource::collection(
            $query->with('expertPanel')
                ->orderBy('gene_symbol')
                ->limit(5)
                ->get()
        );
    }


}
