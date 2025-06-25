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
        $expertPanel = ExpertPanel::factory()->create();
        $curator = User::factory()->create(['email' => 'curator@example.com']);

        $payload = [
            'expert_panel_id' => $expertPanel->id,
            'rows' => [
                [
                    'gene_symbol' => 'BRCA1',
                    'curator_email' => $curator->email,                    
                    'omim_id_1' => 605724,
                    'omim_id_2' => null,
                    'omim_id_3' => null,
                    'omim_id_4' => null,
                    'omim_id_5' => null,
                    'omim_id_6' => null,
                    'omim_id_7' => null,
                    'omim_id_8' => null,
                    'omim_id_9' => null,
                    'omim_id_10' => null,
                    'mondo_id' => null,
                    'disease_entity_if_there_is_no_mondo_id' => null,
                    'rationale_1' => 'Assertion',
                    'rationale_2' => 'Molecular mechanism',
                    'rationale_3' => null,
                    'rationale_4' => null,
                    'rationale_5' => null,
                    'rationale_notes' => 'notes on the rationale',
                    'pmid_1' => 819281721,
                    'pmid_2' => 123198121,
                    'pmid_3' => null,
                    'pmid_4' => null,
                    'pmid_5' => null,
                    'pmid_6' => null,
                    'pmid_7' => null,
                    'pmid_8' => null,
                    'pmid_9' => null,
                    'pmid_10' => null,
                    'date_uploaded' => now()->toDateString(),
                    'precuration_date' => now()->subDays(1)->toDateString(),
                    'disease_entity_assigned_date' => now()->subDays(2)->toDateString(),
                    'curation_in_progress_date' => now()->subDays(3)->toDateString(),
                    'curation_provisional_date' => now()->subDays(4)->toDateString(),
                    'curation_approved_date' => now()->subDays(5)->toDateString(),
                ]
            ]
        ];

        // Act
        $response = $this->postJsonToClientApi('bulk-curations', $payload);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'message',
            'results' => [
                [
                    'row',
                    'status',
                    'curation_id',
                    'gene_symbol',
                ]
            ]
        ]);
        $this->assertEquals('GENE1', $response->json('results.0.gene_symbol'));
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

        $response = $this->postJsonToClientApi('bulk-curations', $payload);

        $response->assertStatus(422);
        $response->assertJsonStructure([
            'message',
            'errors'
        ]);
    }
}
