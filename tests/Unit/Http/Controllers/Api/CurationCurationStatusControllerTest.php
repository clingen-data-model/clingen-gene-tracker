<?php

namespace Tests\Unit\Http\Controllers\Api;

use App\User;
use App\Curation;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class CurationCurationStatusControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function setUp()
    {
        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->curation = factory(Curation::class)->create();
        $this->curation->curationStatuses()->attach([2, 3]); // not including id 1 b/c it's added on creation -tjw
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
        $response = $this->json('POST', '/api/curations/'.$this->curation->id.'/statuses/', [
                'curation_status_id' => 4,
                'status_date' => '1977-09-16'
            ])
            ->assertStatus(200)
            ->assertJson($this->curation->fresh()->curationStatuses->last()->toArray());

        $this->assertEquals(4, $this->curation->fresh()->curationStatuses->count());
        $this->assertEquals('1977-09-16', $this->curation->fresh()->statuses->last()->pivot->status_date->format('Y-m-d'));
    }

    public function test_validates_create_data()
    {
        $this->json('POST', '/api/curations/'.$this->curation->id.'/statuses/', [])
            ->assertStatus(422);

        $this->json('POST', '/api/curations/'.$this->curation->id.'/statuses/', [
            'curation_status_id' => 3000
        ])->assertStatus(422);

        $this->json('POST', '/api/curations/'.$this->curation->id.'/statuses/', [
            'curation_status_id' => 1,
            'status_date' => '09/16/1988'
        ])->assertStatus(422);

        $this->json('POST', '/api/curations/'.$this->curation->id.'/statuses/', [
            'curation_status_id' => 1,
            'status_date' => 'bob'
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
        $this->json('PUT', '/api/curations/'.$this->curation->id.'/statuses/'.$this->curation->statuses->first()->pivot->id, [
            'status_date' => '1982-05-17'
        ])
        ->assertStatus(200)
        ->assertJson($this->curation->fresh()->statuses->first()->toArray());

        $this->assertEquals('1982-05-17', $this->curation->fresh()->statuses->first()->pivot->status_date->format('Y-m-d'));
    }

    public function test_update_status_date_validates_date()
    {
        $this->json('PUT', '/api/curations/'.$this->curation->id.'/statuses/'.$this->curation->statuses->first()->pivot->id, [
            'status_date' => 'ted'
        ])->assertStatus(422);

        $this->json('PUT', '/api/curations/'.$this->curation->id.'/statuses/'.$this->curation->statuses->first()->pivot->id, [
            'status_date' => '09/16/1988'
        ])->assertStatus(422);
    }

    public function test_removes_related_curation_status()
    {
        $this->json('DELETE', '/api/curations/'.$this->curation->id.'/statuses/'.$this->curation->statuses->first()->pivot->id)
            ->assertStatus(204);
        $this->assertDatabaseMissing('curation_curation_status', ['id' => $this->curation->statuses->first()->pivot->id]);
    }
}
