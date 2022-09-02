<?php

namespace App\Http\Controllers\ExternalApi;

use App\Curation;
use App\ModelSearchService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\CurationResource;
use OpenApi\Attributes as OA;

/** 
 * @OA\Info(title="GeneTracker API", version="1.0")
 */
 
class PrecurationResourceController extends Controller
{
    /**
     * Display a paginated listing of the curations.
     * 
     * 
     * @return \Illuminate\Http\Response
     * 
     * @OA\Get(
     *    path="/api/v1/pre-curations",
     *    tags={"precurations"},
     *    @OA\Parameter(
     *        name="where",
     *        in="query",
     *        required=false,
     *        @OA\Schema( 
     *            type="array",
     *            description="Associative array to add where clauses to query.",
     *            @OA\Items(type="string")
     *        )
     *     ),
     *     @OA\Parameter(
     *         name="with", in="query",
     *         description="Relationships to include",
     *         @OA\Schema(type="array", @OA\Items(type="string"))
     *     ),
     *     @OA\Parameter(
     *         name="without", in="query",
     *         description="Default relationships to skip=default relationships include type and currentStatus.",
     *         @OA\Schema(type="array", @OA\Items(type="string"))
     *     ),
     *     @OA\Parameter(
     *         name="sort", in="query",
     *         description="Associative array with keys 'field' and 'dir' where field is the field on which sort and dir is the dircection (asc, desc)",
     *         @OA\Schema(type="array", @OA\Items(type="string"))
     *     ),
     *     @OA\Parameter(
     *         name="page_size", in="query",
     *         description="Number of items to return at one time. Default=20",
     *         @OA\Schema(type="int")
     *     ),
     *     @OA\Parameter(
     *         name="page", in="query",
     *         description="Page to retrieve, based on page_size.",
     *         @OA\Schema(type="int")
     *     ),
     * 
     *     @OA\Response(response=200, description="A paginated list of curations"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Unauthorized"),
     *     @OA\Response(response="default", description="An unexpected error occurred.")
     * )
     * 
     */
    public function index(Request $request)
    {
        $search = new ModelSearchService(
            modelClass: Curation::class, 
            defaultWith: ["currentStatus", 'expertPanel', 'disease']
        );

        $query = $search->buildQuery($request->only(['where', 'with', 'sort', 'with', 'without', 'showDeleted']));

        $results = $query->paginate(
            perPage: $request->get('page_size', 20),
            page: $request->get('page', 1)
        );
        return CurationResource::collection($results);

    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Curation  $curation
     * @return \Illuminate\Http\Response
     * 
     * @OA\Get(
     *     path="/api/v1/pre-curuations/{precurationId}",
     *     summary="Get detailed precuration record",
     *     tags={"precurations"},
     *     @OA\Parameter(
     *         name="precurationId", 
     *         in="path", 
     *         required=true, 
     *         description="Numeric ID or GT UUID of precuration record, or GDM UUID associated with precuration record.",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Precuration record"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Precuration record not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function show($curationId)
    {
        $curation = Curation::find($curationId);

        if (!$curation) {
            $curation = Curation::findByUuid($curationId);
        }

        if (!$curation) {
            $curation == Curation::findByGdmUuid($curationId);
        }

        if (!$curation) {
            return response('Curation not found', 404);
        }

        return new CurationResource(
            $curation->load([
                'phenotypes',
                'rationales',
                'moi',
                'disease',
                'curationType',
                'expertPanel',
                'expertPanel.affiliation',
                'curator'
            ]));
    }
}
