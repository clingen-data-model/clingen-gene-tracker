<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class ClientApiTest extends TestCase
{
    protected string $accessToken;

    public function setUp(): void
    {
        parent::setUp();

        $response = Http::asForm()->post(config('services.clientapi.token_url'), [
            'grant_type' => 'client_credentials',
            'client_id' => config('services.clientapi.client_id'),
            'client_secret' => config('services.clientapi.client_secret'),
            'scope' => '',
        ]);

        $this->assertTrue($response->successful(), 'Failed to get token: ' . $response->body());

        $this->accessToken = $response->json()['access_token'];
    }

    protected function postJsonToClientApi(string $endpoint, array $data = []): \Illuminate\Testing\TestResponse
    {
        return $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->accessToken,
            'Accept' => 'application/json',
        ])->postJson("/api/{$endpoint}", $data);
    }

    /** @test */
    public function it_can_search_genes()
    {
        $res = $this->postJsonToClientApi('client/v1/genes/search', ['query' => 'BRCA']);

        $res->assertOk()
            ->assertJsonStructure(['success', 'data' => ['count', 'results']]);
    }

    /** @test */
    public function it_can_get_gene_by_id()
    {
        $res = $this->postJsonToClientApi('client/v1/genes/byid', ['hgnc_id' => 1100]);

        $res->assertOk()
            ->assertJsonStructure(['success', 'data' => ['hgnc_id', 'gene_symbol']]);
    }

    /** @test */
    public function it_can_get_gene_by_symbol()
    {
        $res = $this->postJsonToClientApi('client/v1/genes/bysymbol', ['gene_symbol' => 'TP53']);

        $res->assertOk()
            ->assertJsonStructure(['success', 'data' => ['hgnc_id', 'gene_symbol']]);
    }

    /** @test */
    public function it_can_search_diseases()
    {
        $res = $this->postJsonToClientApi('client/v1/diseases/search', ['query' => 'cancer']);

        $res->assertOk()
            ->assertJsonStructure(['success', 'data' => ['count', 'results']]);
    }

    /** @test */
    public function it_can_get_disease_by_mondo_id()
    {
        $res = $this->postJsonToClientApi('client/v1/diseases/mondo', ['mondo_id' => 'MONDO:0005148']);

        $res->assertOk()
            ->assertJsonStructure(['success', 'data' => ['id', 'name']]);
    }

    /** @test */
    public function it_can_get_disease_by_ontology_id()
    {
        $res = $this->postJsonToClientApi('client/v1/diseases/ontology', ['ontology_id' => 'MONDO:0005148']);

        $res->assertOk()
            ->assertJsonStructure(['success', 'data' => ['ontology', 'ontology_id', 'name']]);
    }
}
