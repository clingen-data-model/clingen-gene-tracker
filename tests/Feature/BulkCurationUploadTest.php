<?php

namespace Tests\Feature;

use App\User;
use Tests\TestCase;
use App\ExpertPanel;
use App\Jobs\BulkCurationProcessor;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;

/**
 * @group bulk-curations
 */
class BulkCurationUploadTest extends TestCase
{
    use DatabaseTransactions;
    
    public function setUp()
    {
        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->expertPanel = factory(ExpertPanel::class)->create();
        $this->expertPanel->users()->attach([$this->user->id=>['is_coordinator'=>1]]);
    }

    /**
     * @test
     */
    public function bulk_upload_page_returns_exists_and_has_link_to_template()
    {
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
    public function bulkUploadHandler_dispatched_on_upload()
    {
        \DB::table('curations')->delete();

        $this->actingAs($this->user)
            ->call('POST', '/bulk-uploads', [
                'bulk_curations' => file_get_contents(base_path('tests/files/bulk_curation_upload_good.xlsx'))
            ]);
        
        $this->assertEquals(3, \DB::table('curations')->count());
    }
}
