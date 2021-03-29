<?php

namespace Tests\Feature;

use App\Curation;
use App\ExpertPanel;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * @group bulk-curations
 */
class BulkCurationUploadTest extends TestCase
{
    use DatabaseTransactions;

    public function setUp(): void
    {
        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->expertPanel = factory(ExpertPanel::class)->create();
        $this->expertPanel->users()->attach([$this->user->id => ['is_coordinator' => 1]]);
    }

    /**
     * @test
     */
    public function bulk_upload_page_exists_and_has_link_to_template()
    {
        $this->withoutExceptionHandling();
        $this->actingAs($this->user)
            ->call('GET', '/bulk-uploads')
            ->assertStatus(200)
            ->assertSee('Download Template')
            ->assertSee('Expert Panel:')
            ->assertSee('Upload File:');
    }

    /**
     * @test
     */
    public function bulkUploadHandler_creates_curations_from_file()
    {
        \DB::table('curations')->delete();

        // $this->withoutExceptionHandling();
        $response = $this->actingAs($this->user)
            ->json('POST', '/bulk-uploads', [
                'expert_panel_id' => $this->expertPanel->id,
                'bulk_curations' => new UploadedFile(
                                            base_path('tests/files/bulk_curation_upload_good.xlsx'),
                                            'bulk_curation_upload_good.xlsx',
                                            'application/xlsx',
                                            null,
                                            true
                                    ),
            ])
            ->assertOk();

        $this->assertEquals(3, \DB::table('curations')->count());
    }

    /**
     * @test
     */
    public function bulkUploadValidatesRows()
    {
        $this->actingAs($this->user)
            ->json('POST', '/bulk-uploads', [
                'expert_panel_id' => $this->expertPanel->id,
                'bulk_curations' => new UploadedFile(
                                            base_path('tests/files/bulk_curation_upload_bad.xlsx'),
                                            'bulk_curation_upload_good.xlsx',
                                            'application/xlsx',
                                            null,
                                            false
                                    ),
            ])
            ->assertStatus(422);
    }

    /**
     * @test
     */
    public function confirms_duplicates_before_saving_file()
    {
        \DB::table('curations')->delete();
        factory(Curation::class)->create(['gene_symbol' => 'MYL2']);

        $response = $this->actingAs($this->user)
            ->call('POST', '/bulk-uploads', [
                'expert_panel_id' => $this->expertPanel->id,
            ], [], [
                'bulk_curations' => new UploadedFile(
                                            base_path('tests/files/bulk_curation_upload_good.xlsx'),
                                            'bulk_curation_upload_good.xlsx',
                                            'application/xlsx',
                                            null,
                                            true
                                    ),
            ])
            ->assertSee('Some of the genes in your upload already have curations in the GeneTracker:')
            ->assertSee('Continue with upload');

        $this->assertNotNull($response->original->path);
        $this->assertNotNull($response->original->duplicates);

        $this->withoutExceptionHandling();
        $nextResponse = $this->actingAs($this->user)
                        ->call('POST', '/bulk-uploads', [
                            'expert_panel_id' => $this->expertPanel->id,
                            'path' => $response->original->path,
                            'continue_duplicate_upload' => 1,
                        ])
                        ->assertOk();

        $this->assertEquals(4, \DB::table('curations')->count());
    }
}
