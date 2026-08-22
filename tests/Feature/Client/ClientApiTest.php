<?php

namespace Tests\Feature\Client;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Laravel\Passport\ClientRepository;
use Tests\SeedsGenes;
use Tests\SeedsDiseases;
use App\Curation;

class ClientApiTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsGenes;
    use SeedsDiseases;

    protected string $accessToken;

    public function setUp(): void
    {
        parent::setUp();
        $this->seedGenes();
        $this->seedDiseases();

        $client = app(ClientRepository::class)->createClientCredentialsGrantClient('test-client');

        $response = $this->postJson('/oauth/token', [
            'grant_type' => 'client_credentials',
            'client_id' => $client->id,
            'client_secret' => $client->plainSecret,
            'scope' => '',
        ]);

        $response->assertOk();
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
    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_search_genes()
    {
        $res = $this->postJsonToClientApi('client/v1/genes/search', ['query' => 'PER']);

        $res->assertOk()
            ->assertJsonStructure([
                        '*' => [
                            "hgnc_id",
                            "gene_symbol",
                            "omim_id",
                            "ncbi_gene_id",
                            "hgnc_name",
                            "hgnc_status",
                            "previous_symbols",
                            "alias_symbols",
                            "date_approved",
                            "date_modified",
                            "date_symbol_changed",
                            "date_name_changed",
                            "created_at",
                            "updated_at",
                            "deleted_at",
                        ]
                    ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_get_gene_by_id()
    {
        // Not the real HGNC ID for this gene, just what's in the test database seeder...
        $res = $this->postJsonToClientApi('client/v1/genes/byid', ['hgnc_id' => 4220]);

        $res->assertOk()
            ->assertJsonStructure(['hgnc_id', 'gene_symbol'])
            ->assertJsonFragment(['hgnc_id' => 4220, 'gene_symbol' => 'GDF5']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_get_gene_by_symbol()
    {
        $res = $this->postJsonToClientApi('client/v1/genes/bysymbol', ['gene_symbol' => 'GDF5']);

        $res->assertOk()
            ->assertJsonStructure(['hgnc_id', 'gene_symbol'])
            ->assertJsonFragment(['hgnc_id' => 4220, 'gene_symbol' => 'GDF5']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_lookup_curations_by_gene_symbols_with_comma_and_newlines()
    {
        $textareaInput = "ACAT2, CBX2\nGDF5\nPER2, PER3\nTBX22, BLNK";

        foreach (preg_split('/[\s,]+/', $textareaInput, -1, PREG_SPLIT_NO_EMPTY) as $symbol) {
            factory(Curation::class)->create(['gene_symbol' => $symbol]);
        }

        $res = $this->postJsonToClientApi('client/v1/bulk-lookup', [
            'gene_symbol' => $textareaInput,
            'with' => ['classifications'],
        ]);
        
        $res->assertOk()->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'gene_symbol',
                    'disease',
                    'mondo_id',
                    'expert_panel',
                    'mode_of_inheritance',
                    'classifications',
                    'curation_type',
                    'current_status',
                    'current_status_date',
                    'phenotypes',
                ],
            ],
            'meta' => [
                'requested_genes',
                'not_found_genes',
            ],
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_search_diseases()
    {
        $res = $this->postJsonToClientApi('client/v1/diseases/search', ['query' => 'hamartoma']);

        $res->assertOk()
            ->assertJsonStructure([
                "*" => [
                    'id',
                    'mondo_id',
                    'doid_id',
                    'name',
                    'is_obsolete',
                    'replaced_by',
                    'created_at',
                    'updated_at'
                ]
            ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_get_disease_by_mondo_ids()
    {
        $res = $this->postJsonToClientApi('client/v1/diseases/mondos', ['mondo_ids' => ['MONDO:0000413', 'MONDO:0000414']]);

        $res->assertOk()->assertJsonStructure([
                    "*" => [
                        'id',
                        'mondo_id',
                        'doid_id',
                        'name',
                        'is_obsolete',
                        'replaced_by'
                    ] ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_get_disease_by_ontology_id()
    {
        $res = $this->postJsonToClientApi('client/v1/diseases/ontology', ['ontology_id' => 'MONDO:0017623']);

        $res->assertOk()
            ->assertJsonStructure(['ontology', 'ontology_id', 'name']);
    }

    
}
