<?php

namespace App\Http\Controllers\ExternalApi;

use App\Curation;
use App\ModelSearchService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\CurationResource;
use OpenApi\Attributes as OA;


#[OA\Info(title: "GeneTracker API", version: "1.0")]
class PrecurationResourceController extends Controller
{
    /**
     * Display a paginated listing of the curations.
     * 
     * 
     * @return \Illuminate\Http\Response
     *
     */
    #[OA\Get(
        path: "api/v1/pre-curations",
        tags: ["precurations"],
        parameters: [
            new OA\Parameter(
                name: "where",
                in: "query",
                required: false,
                schema: new OA\Schema( 
                    type: "array",
                    description: "Associative array to add where clauses to query.",
                    items: new OA\Items(type: "string")
                )
            ),
            new OA\Parameter(
                name: "with", in: "query",
                description: "Relationships to include",
                schema: new OA\Schema(type: "array", items: new OA\Items(type: "string"))
            ),
            new OA\Parameter(
                name: "without", in: "query",
                description: "Default relationships to skip: default relationships include type and currentStatus.",
                schema: new OA\Schema(type: "array", items: new OA\Items(type: "string"))
            ),
            new OA\Parameter(
                name: "sort", in: "query",
                description: "Associative array with keys 'field' and 'dir' where field is the field on which sort and dir is the dircection (asc, desc)",
                schema: new OA\Schema(type: "array", items: new OA\Items(type: "string"))
            ),
            new OA\Parameter(
                name: "page_size", in: "query",
                description: "Number of items to return at one time. Default: 20",
                schema: new OA\Schema(type: "int")
            ),
            new OA\Parameter(
                name: "page", in: "query",
                description: "Page to retrieve, based on page_size.",
                schema: new OA\Schema(type: "int")
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: "A paginated list of curations"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 403, description: "Unauthorized"),
            new OA\Response(response: "default", description: "An unexpected error occurred.")
        ]
    )]
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
     */ 
    #[OA\Get(
        path: "/api/v1/pre-curuations/{precurationId}",
        summary: "Get detailed precuration record",
        tags: ["precurations"],
        parameters: [
            new OA\Parameter(
            name: "precurationId", 
            in: "path", 
            required: true, 
            description: "Numeric ID or GT UUID of precuration record, or GDM UUID associated with precuration record.",
            schema: new OA\Schema(type: "string")
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Precuration record"
            ),
            new OA\Response(
                response: 404,
                description: "Precuration record not found"
            ),
            new OA\Response(
                response: 401,
                description: "Unauthenticated"
            ),
            new OA\Response(
                response: 403,
                description: "Unauthorized"
            )
        ]
    )]
    public function show($curationId)
    {
        $curation = Curation::findByAnyId($curationId);

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
