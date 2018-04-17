<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TopicCreateRequest;
use App\Http\Resources\TopicResource;
use App\Jobs\Topics\SyncPhenotypes;
use App\Services\RequestDataCleaner;
use App\Topic;
use Illuminate\Http\Request;

class TopicController extends Controller
{
    protected $cleaner;

    protected $validFilters = [
        'gene_symbol',
        'expert_panel_id',
        'curator_id',
        'phenotype'
    ];

    public function __construct(RequestDataCleaner $cleaner)
    {
        $this->cleaner = $cleaner;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = Topic::with('topicStatus');
        foreach ($request->all() as $key => $value) {
            if ($key == 'with') {
                $query->with($value);
            }
            if (in_array($key, $this->validFilters)) {
                $query->where($key, $value);
            }
        }
        $output = TopicResource::collection($query->get()->keyBy('id'));

        return $output;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(TopicCreateRequest $request)
    {
        $topicData = $request->dateParsed('curation_date');

        $topic = Topic::create($topicData);
        \Bus::dispatch(new SyncPhenotypes($topic, $request->phenotypes));
        $topic->load('phenotypes', 'expertPanel', 'curator', 'topicStatus');

        return new TopicResource($topic);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $topic = Topic::findOrFail($id);
        $topic->load('phenotypes', 'expertPanel', 'curator', 'topicStatus');

        return new TopicResource($topic);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(TopicCreateRequest $request, $id)
    {
        $topic = Topic::findOrFail($id);
        $topic->update($request->dateParsed('curation_date'));
        \Bus::dispatch(new SyncPhenotypes($topic, $request->phenotypes));
        $topic->load('phenotypes', 'expertPanel', 'curator', 'topicStatus');

        return new TopicResource($topic);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
    }
}
