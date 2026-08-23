<?php

namespace Tests\Unit\Http\Controllers\Api;

use App\Rationale;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * @group api
 * @group rationales
 */
#[\PHPUnit\Framework\Attributes\Group('api')]
#[\PHPUnit\Framework\Attributes\Group('rationales')]
class RationaleControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function setUp(): void
    {
        parent::setUp();
        $this->rationales = factory(Rationale::class, 3)->create();
        $this->user = factory(\App\User::class)->create();
    }

    /**
     * @test
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function index_returns_all_rationales()
    {
        $response = $this->actingAs($this->user, 'api')
            ->call('GET', '/api/rationales');
        $this->assertEquals(Rationale::all()->pluck('id')->toArray(), $response->original->pluck('id')->toArray());
    }
}
