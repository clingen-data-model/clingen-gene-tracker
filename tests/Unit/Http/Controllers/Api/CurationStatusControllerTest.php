<?php

namespace Tests\Unit\Http\Controllers\Api;

use App\CurationStatus;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * @group api
 * @group curations
 * @group curation-statuses
 * @group controllers
 * @group curations-statuses-controller
 */
#[\PHPUnit\Framework\Attributes\Group('api')]
#[\PHPUnit\Framework\Attributes\Group('curations')]
#[\PHPUnit\Framework\Attributes\Group('curation-statuses')]
#[\PHPUnit\Framework\Attributes\Group('controllers')]
#[\PHPUnit\Framework\Attributes\Group('curations-statuses-controller')]
class CurationStatusControllerTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @test
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function index_lists_curation_statuses()
    {
        $u = factory(\App\User::class)->create();
        $statuses = CurationStatus::all();
        $this->actingAs($u, 'api')
            ->call('GET', '/api/curation-statuses')
            ->assertJson($statuses->toArray());
    }
}
