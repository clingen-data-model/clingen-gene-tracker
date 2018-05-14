<?php

namespace Tests\Unit\Http\Controllers\Api;

use App\Http\Resources\TopicResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group api
 * @group topics
 * @group controllers
 * @group topics-controller
 */
class TopicControllerTest extends TestCase
{
    use RefreshDatabase;

    public function setUp()
    {
        parent::setUp();
        $this->topics = factory(\App\Topic::class, 10)->create();
        $this->user = factory(\App\User::class)->create();
        $this->panel = factory(\App\ExpertPanel::class)->create();
        $this->rationale = factory(\App\Rationale::class)->create();
        $this->curationType = factory(\App\CurationType::class)->create();
    }

    /**
     * @test
     */
    public function index_lists_all_topics()
    {
        $topic = $this->topics->first();
        $topic->update([
            'curator_id' => $this->user->id,
            'expert_panel_id' => $this->panel->id
        ]);

        $topicResource = new TopicResource($this->topics);
        $this->actingAs($this->user, 'api')
            ->call('GET', '/api/topics')
            ->assertStatus(200);
    }

    /**
     * @test
     */
    public function index_lists_topics_filtered_by_gene_sybmol()
    {
        $testGene = 'TEST123';
        $topic = factory(\App\Topic::class, 16)->create(['gene_symbol'=>$testGene]);

        $response = $this->actingAs($this->user, 'api')
            ->call('GET', '/api/topics?gene_symbol='.$testGene);

        $this->assertEquals(16, $response->original->count());
    }

    /**
     * @test
     */
    public function stores_new_topic()
    {
        $data = [
            'gene_symbol' => 'MILTON-1',
            'expert_panel_id' => $this->panel->id
        ];

        $this->actingAs($this->user, 'api')
            ->json('POST', '/api/topics', $data)
            ->assertStatus(201)
            ->assertJsonFragment(['gene_symbol' => 'MILTON-1']);
    }

    /**
     * @test
     */
    public function requires_gene_symbol()
    {
        $data = [
            'expert_panel' => $this->panel->id
        ];

        $response = $this->actingAs($this->user, 'api')
            ->json('POST', '/api/topics/', $data)
            ->assertStatus(422)
            ->assertJson([
                'errors'=>[
                    'gene_symbol'=>[
                        "The gene symbol field is required."
                    ]
                ]
            ]);
    }

    /**
     * @test
     */
    public function requires_expert_panel_id()
    {
        $data = [
            'gene_symbol' => 'ABCD'
        ];

        $response = $this->actingAs($this->user, 'api')
            ->json('POST', '/api/topics/', $data)
            ->assertStatus(422)
            ->assertJson([
                'errors'=>[
                    'expert_panel_id'=>[
                        "The expert panel id field is required."
                    ]
                ]
            ]);
    }

    /**
     * @test
     */
    public function requires_existing_curation_type_id_on_update()
    {
        $topic = factory(\App\Topic::class)->create();
        $curationType = factory(\App\CurationType::class)->create();
        $data = [
            'gene_symbol' => 'ABCD',
            'expert_panel_id' => $this->panel->id,
            'page' => 'curation-types',
        ];

        $response = $this->actingAs($this->user, 'api')
            ->json('PUT', '/api/topics/'.$topic->id, $data)
            ->assertStatus(422)
            ->assertJson([
                'errors'=>[
                    'curation_type_id' => [
                        'A curation type is required to continue'
                    ]
                ]
            ]);

        $data['curation_type_id'] = $curationType->id;

        $response = $this->actingAs($this->user, 'api')
            ->json('PUT', '/api/topics/'.$topic->id, $data)
            ->assertStatus(200);
    }

    /**
     * @test
     */
    public function index_does_not_include_phenotypes_when_not_requested()
    {
        $this->disableExceptionHandling();
        $phenotypes = factory(\App\Phenotype::class, 3)->create();
        $this->topics->each(function ($t) use ($phenotypes) {
            $t->phenotypes()->sync($phenotypes->pluck('id'));
        });

        $response = $this->actingAs($this->user, 'api')
            ->call('GET', '/api/topics/')
            ->assertDontSee('"mim_number":"'.$phenotypes->first()->mim_number.'"');
    }

    /**
     * @test
     */
    public function index_includes_phenotypes_when_requested()
    {
        $this->disableExceptionHandling();
        $phenotypes = factory(\App\Phenotype::class, 3)->create();
        $this->topics->each(function ($t) use ($phenotypes) {
            $t->phenotypes()->sync($phenotypes->pluck('id'));
        });

        $response = $this->actingAs($this->user, 'api')
            ->call('GET', '/api/topics/?with=phenotypes')
            ->assertSee('"mim_number":"'.$phenotypes->first()->mim_number.'"');
    }

    /**
     * @test
     */
    public function index_includes_status_by_default()
    {
        $this->disableExceptionHandling();
        $status = factory(\App\TopicStatus::class)->create();
        $this->topics->each(function ($t) use ($status) {
            $t->topicStatuses()->attach($status->id);
        });

        $response = $this->actingAs($this->user, 'api')
            ->call('GET', '/api/topics')
            ->assertJsonFragment($status->toArray());
    }

    /**
     * @test
     */
    public function topic_show_includes_phenotypes_by_default()
    {
        $phenotypes = factory(\App\Phenotype::class, 3)->create();
        $this->topics->each(function ($t) use ($phenotypes) {
            $t->phenotypes()->sync($phenotypes->pluck('id'));
        });

        $response = $this->actingAs($this->user, 'api')
            ->call('GET', '/api/topics/1')
            ->assertSee('"mim_number":"'.$phenotypes->first()->mim_number.'"');
    }

    /**
     * @test
     */
    public function topic_show_includes_rationale_by_default()
    {
        $this->topics->first()->rationale()->associate($this->rationale);
        $this->actingAs($this->user, 'api')
            ->call('GET', '/api/topics/'.$this->topics->first()->id)
            ->assertSee('"rationale":');
    }

    /**
     * @test
     */
    public function topic_show_includes_curationType_by_default()
    {
        $this->topics->first()->curationType()->associate($this->curationType);
        // dd($this->topics->first()->curationType);
        $this->actingAs($this->user, 'api')
            ->call('GET', '/api/topics/'.$this->topics->first()->id)
            ->assertSee('"curation_type":');
    }

    /**
     * @test
     */
    public function store_saves_phenotypes_for_new_topic()
    {
        $phenotype = factory(\App\Phenotype::class)->create();
        $curator = factory(\App\User::class)->create();

        $data = [
            'gene_symbol' => 'MLTN1',
            'expert_panel_id' => $this->panel->id,
            'curator_id' => $curator->id,
            'phenotypes' => [
                12345,
                67890,
                $phenotype->mim_number
            ]
        ];

        $this->actingAs($this->user, 'api')
            ->call('POST', '/api/topics', $data)
            ->assertSee('"mim_number":"'.$phenotype->mim_number.'"')
            ->assertSee('"mim_number":"12345"')
            ->assertSee('"mim_number":"67890"');
    }

    /**
     * @test
     */
    public function store_saves_new_topic_status_when_set()
    {
        $statuses = factory(\App\TopicStatus::class, 3)->create();
        $curator = factory(\App\User::class)->create();

        $data = [
            'gene_symbol' => 'MLTN1',
            'expert_panel_id' => $this->panel->id,
            'curator_id' => $curator->id,
            'topic_status_id' => $statuses->first()->id
        ];

        $this->actingAs($this->user, 'api')
            ->call('POST', '/api/topics', $data)
            ->assertStatus(201);

        $this->assertDatabaseHas('topic_topic_status', ['topic_status_id'=>$statuses->first()->id]);
    }

    /**
     * @test
     */
    public function update_saves_new_topic_status_when_set()
    {
        $statuses = factory(\App\TopicStatus::class, 3)->create();
        $curator = factory(\App\User::class)->create();

        $topic = $this->topics->first();
        $topic->topicStatuses()->attach([$statuses->first()->id => ['created_at' => today()->subDays(10)]]);

        $data = [
            'page' => 'info',
            'gene_symbol' => $topic->gene_symbol,
            'expert_panel_id' => $this->panel->id,
            'curator_id' => $curator->id,
            'topic_status_id' => $statuses->get(1)->id,
        ];

        $this->disableExceptionHandling();
        $this->actingAs($this->user, 'api')
            ->call('PUT', '/api/topics/'.$topic->id, $data)
            ->assertStatus(200);

        $this->assertDatabaseHas('topic_topic_status', ['topic_status_id'=>$statuses->get(1)->id, 'created_at'=>now()]);
    }

    /**
     * @test
     */
    public function updates_phenotypes_for_new_topic()
    {
        $phenotype = factory(\App\Phenotype::class)->create();
        $phenotype2 = factory(\App\Phenotype::class)->create();
        $this->topics->first()->phenotypes()->attach($phenotype2->id);

        $data = [
            'page' => 'phenotypes',
            'gene_symbol' => 'ABCD',
            'expert_panel_id' => $this->panel->id,
            'phenotypes' => [
                12345,
                67890,
                $phenotype->mim_number
            ],
            'rationale_id' => $this->rationale->id
        ];

        $this->actingAs($this->user, 'api')
            ->call('PUT', '/api/topics/'.$this->topics->first()->id, $data)
            ->assertSee('"mim_number":"'.$phenotype->mim_number.'"')
            ->assertSee('"mim_number":"12345"')
            ->assertSee('"mim_number":"67890"');
    }

    /**
     * @test
     */
    public function store_transforms_comma_separated_pmds_into_array()
    {
        $data = array_merge($this->topics->first()->toArray(), [
            'page'=>'info',
            'pmids'=> 'test,beans,monkeys'
        ]);
        $response = $this->actingAs($this->user, 'api')
            ->call('PUT', '/api/topics/'.$this->topics->first()->id, $data)
            ->assertSee('"pmids":["test","beans","monkeys"]');

        $data = array_merge($this->topics->first()->toArray(), [
            'page'=>'info',
            'pmids'=> ["test","beans","monkeys"]
        ]);
        $response = $this->actingAs($this->user, 'api')
            ->call('PUT', '/api/topics/'.$this->topics->first()->id, $data)
            ->assertSee('"pmids":["test","beans","monkeys"]');
    }

    /**
     * @test
     */
    public function stores_isolated_phenotype_on_isolated_phenotype_curation()
    {
        $this->disableExceptionHandling();
        $topic = $this->topics->first();

        $data = $topic->toArray();
        $data['page'] = 'phenotypes';
        $data['rationale_id'] = $this->rationale->id;
        $data['isolated_phenotype'] = '88888888';
        $response = $this->actingAs($this->user, 'api')
            ->call('PUT', '/api/topics/'.$topic->id, $data);

        $response->assertSee('"mim_number":"88888888"');
    }
}
