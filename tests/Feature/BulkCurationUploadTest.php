<?php

namespace Tests\Feature;

use App\User;
use Tests\TestCase;
use App\ExpertPanel;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class BulkCurationUploadTest extends TestCase
{
    use DatabaseTransactions;
    
    public function setUp()
    {
        parent::setUp();
        $this->user = factory(User::class)->create();
        $this->expertPanel = factory(ExpertPanel::class)->create();
        $this->expertPanel->users()->attach($this->user);
    }
}
