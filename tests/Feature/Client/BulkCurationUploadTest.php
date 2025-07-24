<?php

namespace Tests\Feature\Client;

use App\ExpertPanel;
use App\User;
use Tests\TestCase;
use Laravel\Passport\ClientRepository;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\SeedsGenes;

class BulkCurationUploadTest extends TestCase
{
    use SeedsGenes;
    use DatabaseTransactions;
    
    protected string $accessToken;
    private $user, $expertPanel;

    public function setUp(): void
    {
        parent::setUp();
        $this->seedGenes();
        $this->user = factory(User::class)->create();
        $this->expertPanel = factory(ExpertPanel::class)->create();
        $this->expertPanel->users()->attach([$this->user->id => ['is_coordinator' => 1]]);

        $client = app(ClientRepository::class)->create(null, 'test-client', '');

        $response = $this->postJson('/oauth/token', [
            'grant_type' => 'client_credentials',
            'client_id' => $client->id,
            'client_secret' => $client->secret,
            'scope' => '',
        ]);
        
        $response->assertStatus(200, 'Failed to get access token: ' . $response->getContent());

        $this->accessToken = $response->json()['access_token'];
    }

    protected function postJsonToClientApi(string $endpoint, array $data = []): \Illuminate\Testing\TestResponse
    {
        return $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->accessToken,
            'Accept' => 'application/json',
        ])->postJson("/api/{$endpoint}", $data);
    }

    public function test_bulk_upload_via_json_rows_succeeds()
    {        
        $payload = [
            'expert_panel_id' => $this->expertPanel->id,
            'rows' => [
                [
                    'gene_symbol' => 'GDF5', 
                    // 'curator_email' => $curator->email, 
                    // "curation_type" => "isolated-phenotype"
                ],
                [
                    'gene_symbol' => 'PER2', 
                    // 'curator_email' => $curator->email, 
                    // "curation_type" => "isolated-phenotype"
                ]
            ]
        ];

        // Act
        $response = $this->postJsonToClientApi('client/v1/genes/bulkupload', $payload);
        
        // Assert
        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                [
                    'row',
                    'status',
                    'curation_id',
                    'gene_symbol',
                ]
            ]
        ]);
        $this->assertEquals('GDF5', $response->json('data.0.gene_symbol'));
    }

    public function test_bulk_upload_returns_error_for_invalid_data()
    {
        $payload = [
            'expert_panel_id' => 999, // invalid expert panel
            'rows' => [
                [
                    'gene_symbol' => '', // required but empty
                    'curator_email' => 'invalid-email',
                    'curation_type' => '',
                ]
            ]
        ];

        $response = $this->postJsonToClientApi('client/v1/genes/bulkupload', $payload);

        $response->assertStatus(422);
        $response->assertJsonStructure([
            'message',
            'errors'
        ]);
    }
}
