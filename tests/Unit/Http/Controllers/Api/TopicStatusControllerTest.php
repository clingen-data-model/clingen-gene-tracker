<?php

namespace Tests\Unit\Http\Controllers\Api;

use App\TopicStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group api
 * @group topics
 * @group topic-statuses
 * @group controllers
 * @group topics-statuses-controller
 */
class TopicStatusControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function index_lists_topic_statuses()
    {
        $u = factory(\App\User::class)->create();
        $statuses = TopicStatus::all();
        $this->actingAs($u, 'api')
            ->call('GET', '/api/topic-statuses')
            ->assertJson($statuses->toArray());
    }
}
