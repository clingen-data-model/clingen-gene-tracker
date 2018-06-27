<?php

namespace Tests\Unit\Models;

use App\CurationStatus;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * @group models
 * @group curation-status
 */
class CurationStatusTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @test
     */
    public function has_fillable_name()
    {
        $curation = new CurationStatus();
        $curation->fill(['name'=>'beans']);
        $curation->save();

        $this->assertEquals('beans', $curation->name);
    }
}
