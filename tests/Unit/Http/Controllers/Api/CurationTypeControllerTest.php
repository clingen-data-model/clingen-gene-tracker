<?php

namespace Tests\Unit\Http\Controllers\Api;

use App\CurationType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * @group api
 * @group controllers
 * @group curation-types
 */
class CurationTypeControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function setUp()
    {
        parent::setUp();
        $this->u = factory(\App\User::class)->create();
        $this->types = factory(\App\CurationType::class)->create();
    }

    /**
     * @test
     */
    public function index_returns_all_curation_types()
    {
        $types = CurationType::all();
        $response = $this->actingAs($this->u, 'api')
            ->call('GET', '/api/curation-types')
            ->assertJson($types->toArray());
    }
}
