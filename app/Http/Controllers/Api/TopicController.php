<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TopicCreateRequest;
use App\Http\Requests\TopicUpdateRequest;
use App\Http\Resources\TopicResource;
use App\Jobs\Topics\SyncPhenotypes;
use App\Services\RequestDataCleaner;
use App\Topic;
use Carbon\Carbon;
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
        $query = Topic::with('topicStatuses');
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
        $data = $request->except('phenotypes', 'topic_status_id');
        $topic = Topic::create($data);
        if ($request->phenotypes) {
            \Bus::dispatch(new SyncPhenotypes($topic, $request->phenotypes));
        }
        if ($request->topic_status_id) {
            $topic->topicStatuses()->attach($request->topic_status_id);
        }
        $this->loadRelations($topic);

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
        $this->loadRelations($topic);

        return new TopicResource($topic);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(TopicUpdateRequest $request, $id)
    {
        $topic = Topic::findOrFail($id);
        $data = $request->except('topic_status_id');
        if (isset($data['pmids']) && is_string($data['pmids'])) {
            $data['pmids'] = array_map(function ($i) {
                return trim($i);
            }, explode(',', $data['pmids']));
        }
        $topic->update($data);

        if ($request->phenotypes) {
            \Bus::dispatch(new SyncPhenotypes($topic, $request->phenotypes));
        }
        if ($request->isolated_phenotype) {
            \Bus::dispatch(new SyncPhenotypes($topic, [$request->isolated_phenotype]));
        }

        if ($request->topic_status_id) {
            $created_at = ($request->topic_status_timestamp) ? Carbon::parse($request->topic_status_timestamp) : now();
            $topic->topicStatuses()->attach([$request->topic_status_id => ['created_at' => $created_at]]);
        }

        $this->loadRelations($topic);

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

    private function loadRelations(&$topic)
    {
        $topic->load(['phenotypes', 'expertPanel', 'curator', 'topicStatuses', 'rationale', 'curationType']);
    }
}
