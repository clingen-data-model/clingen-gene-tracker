<?php

namespace Tests\Unit\models;

use App\Curation;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use App\CurationStatus;

/**
 * @group curations
 * @group models
 */
class CurationTest extends TestCase
{
    use DatabaseTransactions;

    public function setUp()
    {
        parent::setUp();
        $this->curation = factory(\App\Curation::class)->create();
    }

    /**
     * @test
     */
    public function curation_has_fillabel_gene_symbol()
    {
        $curation = new Curation();
        $curation->fill(['gene_symbol'=>'TEST-1']);

        $this->assertEquals('TEST-1', $curation->gene_symbol);
    }

    /**
     * @test
     */
    public function curation_has_fillable_modo_id()
    {
        $curation = factory(\App\Curation::class)->create();
        $curation->update(['mondo_id' => 1234567890]);

        $this->assertEquals(1234567890, $curation->mondo_id);
    }

    /**
     * @test
     */
    public function curation_has_fillable_curation_type_id()
    {
        $curation = factory(\App\Curation::class)->create();
        $curation->update(['curation_type_id' => 1]);

        $this->assertEquals(1, $curation->curation_type_id);
    }

    /**
     * @test
     */
    public function curation_has_fillable_mondo_id()
    {
        $curation = factory(\App\Curation::class)->create();
        $curation->update(['mondo_id' => 'MONDO:00012']);

        $this->assertEquals('MONDO:00012', $curation->mondo_id);
    }

    /**
     * @test
     */
    public function curation_has_fillable_disease_entity_notes()
    {
        $curation = factory(\App\Curation::class)->create();
        $curation->update(['disease_entity_notes' => 'test beans monkeys']);

        $this->assertEquals('test beans monkeys', $curation->disease_entity_notes);
    }

    /**
     * @test
     */
    public function curation_has_fillable_pmids()
    {
        $this->curation->update(['pmids'=>[12345,123455,1231523523]]);

        $this->assertEquals([12345,123455,1231523523], $this->curation->pmids);
    }

    /**
     * @test
     */
    public function curation_has_fillable_rationale_notes()
    {
        $content = 'some notes about rationale.';
        $this->curation->update(['rationale_notes' => $content]);
        $this->assertEquals($content, $this->curation->rationale_notes);
    }

    /**
     * @test
     */
    public function curation_has_curator_relationship_to_users()
    {
        $user = factory(\App\User::class)->create();
        $curation = factory(\App\Curation::class)->create([
            'curator_id' => $user->id
        ]);

        $this->assertEquals($user->name, $curation->curator->name);
    }

    /**
     * @test
     */
    public function curation_has_expertPanel_relationship_to_expert_panels()
    {
        $panel = factory(\App\ExpertPanel::class)->create();
        $curation = factory(\App\Curation::class)->create([
            'expert_panel_id' => $panel->id
        ]);

        $this->assertEquals($panel->name, $curation->expertPanel->name);
    }

    /**
     * @test
     */
    public function curation_belongsToMany_phenotypes()
    {
        $curation = factory(\App\Curation::class)->create();
        $phenotypes = factory(\App\Phenotype::class, 2)->create();
        $curation->phenotypes()->attach($phenotypes->pluck('id'));
        $this->assertInstanceOf(BelongsToMany::class, $curation->phenotypes());
        $this->assertEquals(2, $curation->phenotypes->count());
    }

    /**
     * @test
     */
    public function curation_belongsToMany_curation_status()
    {
        $curationStatuses = CurationStatus::limit(2)->get();
        $curation = factory(\App\Curation::class)->create();

        $this->assertInstanceOf(BelongsToMany::class, $curation->curationStatuses());

        $curation->curationStatuses()->attach($curationStatuses->last()->id);

        $this->assertEquals($curationStatuses->pluck('id'), $curation->curationStatuses->pluck('id'));
        $this->assertNotNull($curation->curationStatuses->first()->pivot->created_at);
        $this->assertNotNull($curation->curationStatuses->last()->pivot->updated_at);
    }

    /**
     * @test
     */
    public function curation_has_one_current_status()
    {
        $curationStatuses = CurationStatus::limit(2)->get();
        $curation = factory(\App\Curation::class)->create();

        $statusesAtTime = $curationStatuses->map(function ($item, $idx) {
            return ['id' => $item->id, 'pivotData' => ['created_at' => today()->addDays($idx)]];
        });

        $curation->curationStatuses()->attach($statusesAtTime->pluck('pivotData', 'id'));

        $this->assertEquals($curationStatuses->last()['id'], $curation->fresh()->currentStatus->id);
    }

    /**
     * @test
     */
    public function curation_belongs_to_a_curation_type()
    {
        $curationType = factory(\App\CurationType::class)->create();
        $curation = factory(\App\Curation::class)->create();
        $curation->curationType()->associate($curationType);

        $this->assertEquals($curationType->id, $curation->curationType->id);
    }

    /**
     * @test
     */
    public function curation_belongs_to_many_rationales()
    {
        $rationale = factory(\App\Rationale::class)->create();
        $curation = factory(\App\Curation::class)->create();

        $curation->rationales()->attach($rationale->id);

        $this->assertInstanceOf(BelongsToMany::class, $curation->rationales());
    }

    /**
     * @test
     */
    public function curation_given_uploaded_status_when_created()
    {
        $curation = factory(\App\Curation::class)->create();
        $this->assertEquals($curation->currentStatus->id, 1);
    }
}
