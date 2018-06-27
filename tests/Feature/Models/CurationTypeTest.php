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

    public function setUp()
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
    public function has_many_topics()
    {
        $topic = factory(\App\Topic::class)->create();
        $this->curationType->topics()->save($topic);

        $this->assertInstanceOf(HasMany::class, $this->curationType->topics());
        $this->assertEquals($topic->id, $this->curationType->topics->first()->id);
    }
}
