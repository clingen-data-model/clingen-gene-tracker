<?php

namespace Tests\Feature;

use App\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group bulk-lookup
 */
class BulkGeneLookupTest extends TestCase
{
    public function setup():void
    {
        parent::setup();
        $this->user = factory(User::class)->create();
    }

}
