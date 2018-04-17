<?php

namespace Tests\Unit\Models;

use App\TopicStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group models
 * @group topic-status
 */
class TopicStatusTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function has_fillable_name()
    {
        $topic = new TopicStatus();
        $topic->fill(['name'=>'beans']);
        $topic->save();

        $this->assertEquals('beans', $topic->name);
    }
}
