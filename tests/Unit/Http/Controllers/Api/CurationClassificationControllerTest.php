<?php

namespace Tests\Unit\Http\Controllers\Api;

use App\User;
use App\Curation;
use Tests\TestCase;
use App\Classification;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

/**
 * @group classifications
 */
class CurationClassificationControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function setUp():void
    {
        parent::setUp();
        $this->actingAs(factory(User::class)->create(), 'api');
        $this->classifications = factory(Classification::class, 2)->create();
        $this->curation = factory(Curation::class)->create([]);
    }

    /**
     * @test
     */
    public function store_returns_422_when_classification_id_not_found()
    {
        $this->json(
                'POST', 
                '/api/curations/'.$this->curation->id.'/classifications', 
                ['classification_date' => '2020-01-01']
            )
            ->assertStatus(422);
    }

    /**
     * @test
     */
    public function store_returns_422_when_classifcation_date_invalid()
    {
        $this->json(
                'POST', 
                '/api/curations/'.$this->curation->id.'/classifications', 
                [
                    'classification_id' => $this->classifications->first()->id,
                    'classification_date' => '9/22/12'
                ]
            )
            ->assertStatus(422);
    }

    /**
     * @test
     */
    public function store_adds_new_classifcation_when_data_valid()
    {
        $this->json(
            'POST',
            '/api/curations/'.$this->curation->id.'/classifications',
                [
                    'classification_id' => $this->classifications->first()->id,
                    'classification_date' => '2020-01-01'
                ]
            )
            ->assertStatus(200);
        
        $this->assertDatabaseHas('classification_curation', [
            'curation_id' => $this->curation->id,
            'classification_id' => $this->classifications->first()->id,
            'classification_date' => '2020-01-01'
        ]);
    }

    /**
     * @test
     */
    public function update_updates_existing_classification_curation_record()
    {
        $this->withoutExceptionHandling();
        $this->curation->classifications()->attach($this->classifications->first()->id);
        $this->json(
            'PUT',
            '/api/curations/'.$this->curation->id.'/classifications/'.$this->curation->classifications->first()->pivot->id,
                [
                    'classification_id' => $this->classifications->last()->id,
                    'classification_date' => '2022-01-01'
                ]
            )
            ->assertStatus(200);
        
        $this->assertDatabaseHas('classification_curation', [
            'curation_id' => $this->curation->id,
            'classification_id' => $this->classifications->last()->id,
            'classification_date' => '2022-01-01'
        ]);
    }
    
    
    
    
    
}
