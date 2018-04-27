<?php

namespace Tests\Unit\Http\Controllers\Api;

use App\Rationale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group api
 * @group rationales
 */
class RationaleControllerTest extends TestCase
{
    use RefreshDatabase;

    public function setUp()
    {
        parent::setUp();
        $this->rationales = factory(Rationale::class, 3)->create();
        $this->user = factory(\App\User::class)->create();
    }

    /**
     * @test
     */
    public function index_returns_all_rationales()
    {
        $response = $this->actingAs($this->user, 'api')
            ->call('GET', '/api/rationales');
        $this->assertEquals(Rationale::all()->pluck('id')->toArray(), $response->original->pluck('id')->toArray());
    }
}
