<?php

namespace Tests\Unit\models;

use App\Topic;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group topics
 * @group models
 */
class TopicTest extends TestCase
{
    use RefreshDatabase;

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
    public function topic_has_fillable_modo_id()
    {
        $topic = factory(\App\Topic::class)->create();
        $topic->update(['mondo_id' => 1234567890]);

        $this->assertEquals(1234567890, $topic->mondo_id);
    }

    /**
     * @test
     */
    public function topic_has_fillable_curation_type_id()
    {
        $topic = factory(\App\Topic::class)->create();
        $topic->update(['curation_type_id' => 1]);

        $this->assertEquals(1, $topic->curation_type_id);
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

    /**
     * @test
     */
    public function topic_belongsTo_topic_status()
    {
        $topicStatus = factory(\App\TopicStatus::class)->create();
        $topic = factory(\App\Topic::class)->create();
        $topic->topicStatus()->associate($topicStatus);
        $topic->save();

        $this->assertEquals($topicStatus->id, $topic->topicStatus->id);
    }

    /**
     * @test
     */
    public function topic_belongs_to_a_curation_type()
    {
        $curationType = factory(\App\CurationType::class)->create();
        $topic = factory(\App\Topic::class)->create();
        $topic->curationType()->associate($curationType);

        $this->assertEquals($curationType->id, $topic->curationType->id);
    }

    /**
     * @test
     */
    public function has_fillable_curation_date()
    {
        $topic = factory(\App\Topic::class)->create();
        $topic->update(['curation_date' => today()]);

        $this->assertEquals(today(), $topic->curation_date);
    }

    /**
     * @test
     */
    public function has_fillable_mondo_id()
    {
        $topic = factory(\App\Topic::class)->create();
        $topic->update(['mondo_id' => 'MONDO:00012']);

        $this->assertEquals('MONDO:00012', $topic->mondo_id);
    }

    /**
     * @test
     */
    public function has_fillable_disease_entity_notes()
    {
        $topic = factory(\App\Topic::class)->create();
        $topic->update(['disease_entity_notes' => 'test beans monkeys']);

        $this->assertEquals('test beans monkeys', $topic->disease_entity_notes);
    }
}
