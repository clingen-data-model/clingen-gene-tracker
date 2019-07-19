<?php

namespace Tests\Unit\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * @group models
 * @group rationale
 */
class RationaleTest extends TestCase
{
    use DatabaseTransactions;

    public function setUp()
    {
        parent::setUp();
        $this->rationale = factory(\App\Rationale::class)->create();
    }

    /**
     * @test
     */
    public function has_fillable_name()
    {
        $this->rationale->update(['name' => 'beans']);

        $this->assertEquals('beans', $this->rationale->name);
    }

    /**
     * @test
     */
    public function rationale_has_many_curations()
    {
        $curation = factory(\App\Curation::class)->create();
        $this->rationale->curations()->save($curation);

        $this->assertInstanceOf(HasMany::class, $this->rationale->curations());
        $this->assertEquals($curation->id, $this->rationale->curations()->first()->id);
    }
}
