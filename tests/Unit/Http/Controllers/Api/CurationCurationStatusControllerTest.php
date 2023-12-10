<?php

namespace Tests\Unit\Http\Controllers\Api;

use App\Curation;
use App\CurationStatus;
use App\Jobs\Curations\AddStatus;
use App\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CurationCurationStatusControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->curation = factory(Curation::class)->make();
        $this->curation->created_at = '1977-01-01';
        $this->curation->save();

        $curationStatuses = CurationStatus::find([2, 3])->keyBy('id');
        // not including id 1 b/c it's added on creation -tjw
        foreach ([2, 3] as $statusId) {
            AddStatus::dispatch($this->curation, $curationStatuses->get($statusId), '1977-01-01');
        }
        $this->actingAs($this->user, 'api');
    }

    public function test_lists_all_statuses_for_curation()
    {
        $response = $this->json('GET', '/api/curations/'.$this->curation->id.'/statuses')
                        ->assertStatus(200)
                        ->assertJson($this->curation->curationStatuses->toArray());
    }

    public function test_relates_new_status_to_curation()
    {
        Carbon::setTestNow(Carbon::tomorrow());
        $response = $this->json('POST', '/api/curations/'.$this->curation->id.'/statuses/', [
            'curation_status_id' => 4,
            'status_date' => '1977-09-16',
        ])
            ->assertStatus(200);

        $this->assertEquals(4, $this->curation->fresh()->curationStatuses->count());
        $this->assertEquals('1977-09-16', $this->curation->fresh()->statuses->last()->pivot->status_date->format('Y-m-d'));
    }

    public function test_validates_create_data()
    {
        $this->json('POST', '/api/curations/'.$this->curation->id.'/statuses/', [])
            ->assertStatus(422);

        $this->json('POST', '/api/curations/'.$this->curation->id.'/statuses/', [
            'curation_status_id' => 3000,
        ])->assertStatus(422);

        $this->json('POST', '/api/curations/'.$this->curation->id.'/statuses/', [
            'curation_status_id' => 1,
            'status_date' => '09/16/1988',
        ])->assertStatus(422);

        $this->json('POST', '/api/curations/'.$this->curation->id.'/statuses/', [
            'curation_status_id' => 1,
            'status_date' => 'bob',
        ])->assertStatus(422);
    }

    public function test_shows_related_curation_status()
    {
        $this->json('GET', '/api/curations/'.$this->curation->id.'/statuses/'.$this->curation->statuses->first()->id)
            ->assertStatus(200)
            ->assertJson($this->curation->statuses->first()->toArray());
    }

    public function test_updates_status_date_of_related_status()
    {
        $ccs = $this->curation->statuses->random();
        $this->json('PUT', '/api/curations/'.$this->curation->id.'/statuses/'.$ccs->pivot->id, [
            'status_date' => '1982-05-17',
        ])
        ->assertStatus(200)
        ->assertJson($ccs->fresh()->toArray());

        $curation = $this->curation->fresh();
        $ccs = $curation->statuses->keyBy('pivot.id')->get($ccs->pivot->id);

        $this->assertEquals('1982-05-17', $ccs->pivot->status_date->format('Y-m-d'));

        $this->assertEquals($ccs->id, $curation->curation_status_id);
    }

    public function test_update_status_date_validates_date()
    {
        $this->json('PUT', '/api/curations/'.$this->curation->id.'/statuses/'.$this->curation->statuses->first()->pivot->id, [
            'status_date' => 'ted',
        ])->assertStatus(422);

        $this->json('PUT', '/api/curations/'.$this->curation->id.'/statuses/'.$this->curation->statuses->first()->pivot->id, [
            'status_date' => '09/16/1988',
        ])->assertStatus(422);
    }

    public function test_removes_related_curation_status()
    {
        $this->json('DELETE', '/api/curations/'.$this->curation->id.'/statuses/'.$this->curation->statuses->first()->pivot->id)
            ->assertStatus(204);
        $this->assertDatabaseMissing('curation_curation_status', ['id' => $this->curation->statuses->first()->pivot->id]);
    }
}
