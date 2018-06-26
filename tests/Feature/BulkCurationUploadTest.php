<?php

namespace Tests\Feature;

use App\User;
use Tests\TestCase;
use App\ExpertPanel;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BulkCurationUploadTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->expertPanel = factory(ExpertPanel::class)->create();
        $this->expertPanel->addUser($this->user);
    }
    
    
    /**
     * @test
     */
    public function csv_upload_screen_exists()
    {
    }
}
