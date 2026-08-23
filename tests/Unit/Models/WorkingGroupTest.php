<?php

namespace Tests\Unit\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * @group models
 * @group working-groups
 */
#[\PHPUnit\Framework\Attributes\Group('models')]
#[\PHPUnit\Framework\Attributes\Group('working-groups')]
class WorkingGroupTest extends TestCase
{
    use DatabaseTransactions;

    public function setUp(): void
    {
        parent::setUp();
        $this->workingGroup = factory(\App\WorkingGroup::class)->create();
    }

    /**
     * @test
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function has_fillable_name()
    {
        $this->workingGroup->fill(['name'=>'test group']);
        $this->workingGroup->save();
        $this->assertEquals('test group', $this->workingGroup->name);
    }

    /**
     * @test
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function uses_soft_deletes()
    {
        $this->workingGroup->delete();

        $this->assertDatabaseHas(
            'working_groups',
            [
                'name' => $this->workingGroup->name,
                'deleted_at'=>$this->workingGroup->deleted_at
            ]
        );
    }

    /**
     * @test
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function is_revisionable_and_tracks_create()
    {
        $this->assertNotNull($this->workingGroup->revisionHistory);
        $this->assertEquals(1, $this->workingGroup->revisionHistory->count());
    }

    /**
     * @test
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function has_many_expert_panels()
    {
        $this->assertInstanceOf(HasMany::class, $this->workingGroup->expertPanels());
    }
}
