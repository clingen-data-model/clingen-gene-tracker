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
        $this->withoutExceptionHandling();
        $this->actingAs($this->user, 'api')
            ->json('GET', $this->baseUrl.$this->disease->mondo_id)
            ->assertStatus(200)
            ->assertJson($this->disease->toArray());
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
        $this->assertContains($d3->toArray(), $response->original);
        $this->assertContains($d4->toArray(), $response->original);
        $this->assertNotContains($d2->toArray(), $response->original);
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
        $this->assertContains($d2->toArray(), $response->original);
        $this->assertContains($d3->toArray(), $response->original);
        $this->assertNotContains($d4->toArray(), $response->original);
    }
    
    
}
