<?php

namespace Tests\Unit\Http\Controllers\Api;

use App\Http\Resources\TopicResource;
use App\Topic;
use Carbon\Carbon;
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
    public function curation_date_must_be_a_date_if_included()
    {
        $data = [
            'gene_symbol' => 'ABCD',
            'expert_panel_id' => $this->panel->id,
        ];

        $response = $this->actingAs($this->user, 'api')
            ->json('POST', '/api/topics/', $data)
            ->assertStatus(201);

        $data['curation_date'] = 'beans';

        $response = $this->actingAs($this->user, 'api')
            ->json('POST', '/api/topics/', $data)
            ->assertStatus(422)
            ->assertJson([
                'errors'=>[
                    'curation_date' => [
                        'The curation date is not a valid date.'
                    ]
                ]
            ]);
    }

    /**
     * @test
     */
    public function parses_curation_date_for_storage_when_creating()
    {
        $data = [
            'gene_symbol' => 'ABCD',
            'expert_panel_id' => $this->panel->id,
            'curation_date' => '09/16/1977'
        ];

        $response = $this->actingAs($this->user, 'api')
            ->json('POST', '/api/topics/', $data)
            ->assertStatus(201);

        $topic = Topic::gene('ABCD')->first();

        $this->assertEquals(Carbon::parse('1977-09-16'), $topic->curation_date);
    }

    /**
     * @test
     */
    public function parses_curation_date_for_storage_when_updating()
    {
        $topic = factory(\App\Topic::class)->create();
        $data = [
            'gene_symbol' => 'ABCD',
            'expert_panel_id' => $this->panel->id,
            'curation_date' => '09/16/1977'
        ];

        $response = $this->actingAs($this->user, 'api')
            ->json('PUT', '/api/topics/'.$topic->id, $data)
            ->assertStatus(200);

        $topic = Topic::gene('ABCD')->first();

        $this->assertEquals(Carbon::parse('1977-09-16'), $topic->curation_date);
    }

    /**
     * @test
     */
    public function does_not_include_phenotypes_when_not_requested()
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
    public function includes_phenotypes_when_requested()
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
    public function includes_status_by_default()
    {
        $this->disableExceptionHandling();
        $status = factory(\App\TopicStatus::class)->create();
        $this->topics->each(function ($t) use ($status) {
            $t->topicStatus()->associate($status);
            $t->save();
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
        $this->disableExceptionHandling();
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
    public function store_saves_phenotypes_for_new_topic()
    {
        $this->disableExceptionHandling();
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
    public function updates_phenotypes_for_new_topic()
    {
        $this->disableExceptionHandling();
        $phenotype = factory(\App\Phenotype::class)->create();
        $phenotype2 = factory(\App\Phenotype::class)->create();
        $this->topics->first()->phenotypes()->attach($phenotype2->id);

        $data = [
            'gene_symbol' => 'ABCD',
            'expert_panel_id' => $this->panel->id,
            'phenotypes' => [
                12345,
                67890,
                $phenotype->mim_number
            ]
        ];

        $this->actingAs($this->user, 'api')
            ->call('PUT', '/api/topics/'.$this->topics->first()->id, $data)
            ->assertSee('"mim_number":"'.$phenotype->mim_number.'"')
            ->assertSee('"mim_number":"12345"')
            ->assertSee('"mim_number":"67890"');
    }
}
