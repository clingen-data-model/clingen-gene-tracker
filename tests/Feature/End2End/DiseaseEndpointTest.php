<?php

namespace Tests\Feature\End2End;

use App\User;
use App\Disease;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group mondo
 */
class DiseaseEndpointTest extends TestCase
{
    public function setup():void
    {
        parent::setup();
        $this->user = factory(User::class)->create();
        $this->disease = factory(Disease::class)->create();
        $this->baseUrl = '/api/diseases/';
    }

    /**
     * @test
     */
    public function gets_disease_data()
    {
        $this->actingAs($this->user, 'api')
            ->json('GET', $this->baseUrl.$this->disease->mondo_id)
            ->assertStatus(200)
            ->assertJsonFragment($this->disease->toArray());
    }
    
    /**
     * @test
     */
    public function validates_mondo_id_format()
    {
        $this->actingAs($this->user, 'api')
            ->json('GET', $this->baseUrl.'MONDO:')
            ->assertStatus(422);
    }
    
    /**
     * @test
     */
    public function gets_mondo_ids_matching_string()
    {        $d2 = factory(Disease::class)->create([
            'mondo_id' => 'MONDO:2234567',
            'name' => 'Hypermyoplasia'
        ]);
        $d3 = factory(Disease::class)->create([
            'mondo_id' => 'MONDO:1234568',
            'name' => 'Inclusion Body Myocitis'
        ]);
        $d4 = factory(Disease::class)->create([
            'mondo_id' => 'MONDO:1234569',
            'name' => 'Early Syndrom'
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->json('GET', $this->baseUrl.'search?query_string=123');
        $response->assertStatus(200);
        $response->assertJsonFragment($d3->toArray());
        $response->assertJsonFragment($d4->toArray());
        $response->assertJsonMissingExact($d2->toArray());
    }
    
    /**
     * @test
     */
    public function searches_name()
    {
        $d2 = factory(Disease::class)->create([
            'mondo_id' => 'MONDO:2234567',
            'name' => 'Hypermyoplasia'
        ]);
        $d3 = factory(Disease::class)->create([
            'mondo_id' => 'MONDO:1234568',
            'name' => 'Inclusion Body Myocitis'
        ]);
        $d4 = factory(Disease::class)->create([
            'mondo_id' => 'MONDO:1234569',
            'name' => 'Early Syndrom'
        ]);

        $this->withoutExceptionHandling();
        $response = $this->actingAs($this->user, 'api')
            ->json('GET', $this->baseUrl.'search?query_string=myo');
        $response->assertStatus(200);
        $response->assertJsonFragment($d2->toArray());
        $response->assertJsonFragment($d3->toArray());
        $response->assertJsonMissingExact($d4->toArray());
    }
    
    
}
