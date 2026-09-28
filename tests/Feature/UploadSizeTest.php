<?php

namespace Tests\Feature;

use App\Curation;
use App\ExpertPanel;
use App\User;
use App\Services\BulkCurationProcessor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UploadSizeTest extends TestCase
{
    private User $user;
    private Curation $curation;

    public function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        Storage::fake(config('filesystems.default'));
        $this->user = factory(User::class)->create();
        $panel = factory(ExpertPanel::class)->create();
        $panel->users()->attach($this->user->id, ['is_coordinator' => true]);
        $this->curation = factory(Curation::class)->create(['expert_panel_id' => $panel->id]);
    }

    public function test_document_uploads_accept_three_and_six_mb_and_reject_over_six_mb(): void
    {
        $this->actingAs($this->user, 'api');
        foreach ([3072, 6144] as $size) {
            $this->postJson('/api/curations/'.$this->curation->id.'/uploads', [
                'curation_id' => $this->curation->id,
                'file' => UploadedFile::fake()->create('document.pdf', $size, 'application/pdf'),
            ])->assertCreated();
            $this->assertDatabaseHas('uploads', ['curation_id' => $this->curation->id, 'file_name' => 'document.pdf']);
        }
        $this->postJson('/api/curations/'.$this->curation->id.'/uploads', [
            'curation_id' => $this->curation->id,
            'file' => UploadedFile::fake()->create('too-large.pdf', 6145, 'application/pdf'),
        ])->assertUnprocessable()->assertJsonValidationErrors('file');
        $this->assertDatabaseMissing('uploads', ['file_name' => 'too-large.pdf']);
    }

    public function test_document_type_validation_is_preserved(): void
    {
        $this->actingAs($this->user, 'api')->postJson('/api/curations/'.$this->curation->id.'/uploads', [
            'curation_id' => $this->curation->id,
            'file' => UploadedFile::fake()->create('program.exe', 10, 'application/x-msdownload'),
        ])->assertUnprocessable()->assertJsonValidationErrors('file');
    }

    public function test_bulk_spreadsheet_uploads_accept_three_and_six_mb_and_reject_over_six_mb(): void
    {
        // Parsing/business validation is covered by BulkCurationUploadTest; test the HTTP size boundary here.
        $this->mock(BulkCurationProcessor::class)->shouldReceive('processFile')->twice()->andReturn(collect());
        $this->actingAs($this->user, 'web');
        foreach ([3072, 6144] as $size) {
            $this->postJson('/bulk-uploads', [
                'expert_panel_id' => $this->curation->expert_panel_id,
                'bulk_curations' => UploadedFile::fake()->create('curations.xlsx', $size, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
            ])->assertOk();
        }
        $this->postJson('/bulk-uploads', [
            'expert_panel_id' => $this->curation->expert_panel_id,
            'bulk_curations' => UploadedFile::fake()->create('too-large.xlsx', 6145),
        ])->assertUnprocessable()->assertJsonValidationErrors('bulk_curations');
    }

    public function test_shared_size_and_blade_bootstrap_display_six_mb(): void
    {
        $this->assertSame(6144, getMaxUploadSize());
        $this->assertSame('6 MB', getMaxUploadSizeForHumans());
        $this->actingAs($this->user, 'web')->get('/home')->assertOk()->assertSee("window.maxUploadSize = '6 MB'", false);
    }
}
