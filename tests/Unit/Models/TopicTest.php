<?php

namespace Tests\Unit\models;

use App\Topic;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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

    public function setUp()
    {
        parent::setUp();
        $this->topic = factory(\App\Topic::class)->create();
    }

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
    public function topic_has_fillable_mondo_id()
    {
        $topic = factory(\App\Topic::class)->create();
        $topic->update(['mondo_id' => 'MONDO:00012']);

        $this->assertEquals('MONDO:00012', $topic->mondo_id);
    }

    /**
     * @test
     */
    public function topic_has_fillable_disease_entity_notes()
    {
        $topic = factory(\App\Topic::class)->create();
        $topic->update(['disease_entity_notes' => 'test beans monkeys']);

        $this->assertEquals('test beans monkeys', $topic->disease_entity_notes);
    }

    /**
     * @test
     */
    public function topic_has_fillable_rationale_id()
    {
        $rationale = factory(\App\Rationale::class)->create();
        $this->topic->update(['rationale_id'=>$rationale->id]);

        $this->assertEquals($rationale->id, $this->topic->rationale_id);
    }

    /**
     * @test
     */
    public function topic_has_fillable_rationale_other()
    {
        $rationale = factory(\App\Rationale::class)->create();
        $content = 'This is a bunch of text for rationale other notes.';
        $this->topic->update(['rationale_other'=>$content]);

        $this->assertEquals($content, $this->topic->rationale_other);
    }

    /**
     * @test
     */
    public function topic_has_fillable_pmids()
    {
        $this->topic->update(['pmids'=>[12345,123455,1231523523]]);

        $this->assertEquals([12345,123455,1231523523], $this->topic->pmids);
    }

    /**
     * @test
     */
    public function topic_has_fillable_rationale_notes()
    {
        $content = 'some notes about rationale.';
        $this->topic->update(['rationale_notes' => $content]);
        $this->assertEquals($content, $this->topic->rationale_notes);
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
    public function topic_belongsToMany_topic_status()
    {
        $topicStatuses = factory(\App\TopicStatus::class, 2)->create();
        $topic = factory(\App\Topic::class)->create();

        $this->assertInstanceOf(BelongsToMany::class, $topic->topicStatuses());

        $topic->topicStatuses()->attach($topicStatuses->last()->id);

        $this->assertEquals($topicStatuses->pluck('id'), $topic->topicStatuses->pluck('id'));
        $this->assertNotNull($topic->topicStatuses->first()->pivot->created_at);
        $this->assertNotNull($topic->topicStatuses->last()->pivot->updated_at);
    }

    /**
     * @test
     */
    public function topic_has_one_current_status()
    {
        $topicStatuses = factory(\App\TopicStatus::class, 2)->create();
        $topic = factory(\App\Topic::class)->create();
        $statusesAtTime = $topicStatuses->transform(function ($item, $idx) {
            return ['id' => $item->id, 'pivotData' => ['created_at' => today()->addDays($idx)]];
        });
        $topic->topicStatuses()->attach($statusesAtTime->pluck('pivotData', 'id'));

        $this->assertEquals($topicStatuses->last()['id'], $topic->currentStatus->id);
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
    public function topic_belongs_to_a_rationale()
    {
        $rationale = factory(\App\Rationale::class)->create();
        $topic = factory(\App\Topic::class)->create();

        $topic->rationale()->associate($rationale);

        $this->assertInstanceOf(BelongsTo::class, $topic->rationale());
        $this->assertEquals($rationale->id, $topic->rationale->id);
    }

    /**
     * @test
     */
    public function topic_given_uploaded_status_when_created()
    {
        \Artisan::call('db:seed', ['--class'=>'TopicStatusesTableSeeder']);
        $topic = factory(\App\Topic::class)->create();
        $this->assertEquals($topic->currentStatus->id, 1);
    }
}
