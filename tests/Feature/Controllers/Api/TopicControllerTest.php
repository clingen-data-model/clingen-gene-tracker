<?php

namespace Tests\Feature\Controllers\Api;

use App\Http\Resources\TopicResource;
use App\User;
use Illuminate\Container\Container;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

/**
 * @group api
 * @group topics
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
}
