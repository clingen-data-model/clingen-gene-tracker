<?php

namespace Tests\Feature\Client;

use App\Curation;
use App\ExpertPanel;
use App\User;
use Tests\TestCase;
use Illuminate\Support\Facades\Http;

class BulkCurationUploadTest extends TestCase
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

    public function test_bulk_upload_via_json_rows_succeeds()
    {        
        $expertPanel = ExpertPanel::inRandomOrder()->firstOrFail();
        $curator = User::whereNotNull('email')->inRandomOrder()->firstOrFail();

        $payload = [
            'expert_panel_id' => $expertPanel->id,
            'rows' => [
                [
                    'gene_symbol' => 'BRCA1', 
                    // 'curator_email' => $curator->email, 
                    // "curation_type" => "isolated-phenotype"
                ],
                [
                    'gene_symbol' => 'ABCA2', 
                    // 'curator_email' => $curator->email, 
                    // "curation_type" => "isolated-phenotype"
                ]
            ]
        ];

        // Act
        $response = $this->postJsonToClientApi('client/v1/genes/bulkupload', $payload);
        
        // Assert
        $response->assertStatus(200);
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
        $this->assertEquals('BRCA1', $response->json('data.0.gene_symbol'));
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
