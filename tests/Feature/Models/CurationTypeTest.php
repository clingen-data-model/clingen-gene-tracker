<?php

namespace Tests\Feature\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * @group models
 * @group curation-type
 */
class CurationTypeTest extends TestCase
{
    use DatabaseTransactions;

    public function setUp(): void
    {
        parent::setUp();
        $this->curationType = factory(\App\CurationType::class)->create();
    }

    /**
     * @test
     */
    public function has_fillable_name()
    {
        $this->curationType->update(['name'=>'beans']);
        $this->assertEquals('beans', $this->curationType->name);
    }

    /**
     * @test
     */
    public function has_many_curations()
    {
        $curation = factory(\App\Curation::class)->create();
        $this->curationType->curations()->save($curation);

        $this->assertInstanceOf(HasMany::class, $this->curationType->curations());
        $this->assertEquals($curation->id, $this->curationType->curations->first()->id);
    }
}
