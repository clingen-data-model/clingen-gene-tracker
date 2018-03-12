<?php

namespace Tests\Feature\models;

use App\Topic;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

/**
 * @group topics
 * @group models
 */
class TopicTest extends TestCase
{
    /**
     * @test
     */
    public function topic_has_fillabel_gene_symbol()
    {
        $topic = new Topic();
        $topic->fill(['gene_symbol'=>'TEST-1']);

        $this->assertEquals('TEST-1', $topic->gene_symbol);
    }

    /**
     * @test
     */
    public function topic_has_curator_relationship_to_users()
    {
        $user = factory(\App\User::class)->create();
        $topic = factory(\App\Topic::class)->create([
            'curator_id' => $user->id
        ]);

        $this->assertEquals($user->name, $topic->curator->name);
    }

    /**
     * @test
     */
    public function topic_has_expertPanel_relationship_to_expert_panels()
    {
        $panel = factory(\App\ExpertPanel::class)->create();
        $topic = factory(\App\Topic::class)->create([
            'expert_panel_id' => $panel->id
        ]);

        $this->assertEquals($panel->name, $topic->expertPanel->name);
    }

    /**
     * @test
     */
    public function topic_belongsToMany_phenotypes()
    {
        $topic = factory(\App\Topic::class)->create();
        $phenotypes = factory(\App\Phenotype::class, 2)->create();
        $topic->phenotypes()->attach($phenotypes->pluck('id'));
        $this->assertInstanceOf(BelongsToMany::class, $topic->phenotypes());
        $this->assertEquals(2, $topic->phenotypes->count());
    }
}
