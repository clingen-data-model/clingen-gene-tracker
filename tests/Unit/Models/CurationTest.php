<?php

namespace Tests\Unit\Models;

use App\Gene;
use App\Upload;
use App\Curation;
use App\Phenotype;
use Carbon\Carbon;
use Tests\TestCase;
use App\Affiliation;
use App\ExpertPanel;
use Ramsey\Uuid\Uuid;
use App\Classification;
use App\CurationStatus;
use App\Events\Curation\Saved;
use App\Jobs\Curations\AddStatus;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @group curations
 * @group models
 */
class CurationTest extends TestCase
{
    use DatabaseTransactions;

    public function setUp(): void
    {
        parent::setUp();
        $this->curation = factory(\App\Curation::class)->create();
    }

    /**
     * @test
     */
    public function curations_is_softDeletable()
    {
        $this->curation->delete();

        Carbon::setTestNow('2021-06-01');
        $this->assertDatabaseHas('curations', [
            'id' => $this->curation->id,
        ]);
        $this->assertNotNull($this->curation->deleted_at);
    }
    

    /**
     * @test
     */
    public function curation_has_fillable_gene_symbol()
    {
        $curation = new Curation();
        $curation->fill(['gene_symbol' => 'TEST-1']);

        $this->assertEquals('TEST-1', $curation->gene_symbol);
    }

    /**
     * @test
     */
    public function curation_has_fillable_modo_id()
    {
        $curation = factory(\App\Curation::class)->create();
        $curation->update(['mondo_id' => 1234567]);

        $this->assertEquals('MONDO:1234567', $curation->mondo_id);
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
        $this->curation->update(['pmids' => [12345, 123455, 1231523523]]);

        $this->assertEquals([12345, 123455, 1231523523], $this->curation->pmids);
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
            'curator_id' => $user->id,
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
            'expert_panel_id' => $panel->id,
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
        $this->assertEquals(2, $curation->phenotypes()->count());
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

        $this->assertEquals($curationStatuses->pluck('id'), $curation->curationStatuses()->get()->pluck('id'));
        $this->assertNotNull($curation->curationStatuses()->first()->pivot->created_at);
        $this->assertNotNull($curation->curationStatuses()->get()->last()->pivot->updated_at);
    }

    /**
     * @test
     */
    public function curation_has_one_current_status()
    {
        $curationStatuses = CurationStatus::limit(2)->get();
        $curation = factory(\App\Curation::class)->create();

        $statusesAtTime = $curationStatuses->map(function ($item, $idx) use ($curation) {
            AddStatus::dispatch($curation, $item, today()->addDays($idx + 1));
        });

        $this->assertEquals($curationStatuses->last()['id'], $curation->fresh()->currentStatus->id);
    }

    /**
     * @test
     */
    public function curation_can_get_current_status_date_attribute()
    {
        $curationStatuses = CurationStatus::limit(2)->get();
        $curation = factory(\App\Curation::class)->create();

        $statusesAtTime = $curationStatuses->map(function ($item, $idx) use ($curation) {
            AddStatus::dispatch($curation, $item, today()->addDays($idx + 1));
        });

        $curation = $curation->fresh();

        $this->assertEquals(Carbon::today()->addDays(2), $curation->currentStatusDate);
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
        $this->assertEquals($curation->fresh()->currentStatus->id, 1);
    }

    /**
     * @test
     */
    public function gets_numeric_mondo_id_attribute()
    {
        $curation = factory(Curation::class)->make(['mondo_id' => 'MONDO:1234']);
        $this->assertEquals(1234, $curation->numericMondoId);

        $curation = factory(Curation::class)->make(['mondo_id' => 'MONDO: 1234']);
        $this->assertEquals(1234, $curation->numericMondoId);
    }

    /**
     * @test
     */
    public function curation_saved_dispatches_augment_hgnc_and_mondo()
    {
        \Event::fake();
        $curation = factory(Curation::class)->create(['gene_symbol' => 'BRCA1', 'mondo_id' => 'MONDO:0000473']);

        \Event::assertDispatched(Saved::class);
    }

    /**
     * @test
     */
    public function curation_belongs_to_many_classifications()
    {
        $classifications = factory(Classification::class, 2)->create();

        $curation = factory(Curation::class)->create([]);

        $curation->classifications()->attach($classifications->pluck('id'));

        $this->assertEquals(2, $curation->classifications()->count());
        $this->assertNotNull($curation->classifications()->first()->pivot->classification_date);
        $this->assertNotNull($curation->classifications()->first()->pivot->created_at);
        $this->assertNotNull($curation->classifications()->first()->pivot->updated_at);
    }

    /**
     * @test
     */
    public function curation_belongs_to_affiliation()
    {
        $curation = factory(Curation::class)->create();

        $affiliation = Affiliation::query()->first();
        $curation->affiliation_id = $affiliation->id;

        $this->assertEquals($affiliation, $curation->affiliation);
    }

    /**
     * @test
     */
    public function can_scope_by_hgnc_and_mondo_ids()
    {
        $curation = factory(Curation::class)->create([
            'hgnc_id' => 17098,
            'mondo_id' => 'MONDO:0011111',
        ]);

        $this->assertEquals($curation->id, Curation::hgncAndMondo(17098, 'MONDO:0011111')->first()->id);
        $this->assertNull(Curation::hgncAndMondo(17098, 'MONDO:0011112')->first());
    }

    /**
     * @test
     */
    public function can_scope_where_curation_does_not_have_uuid()
    {
        $curationWithUuid = factory(Curation::class)->create([
            'hgnc_id' => 17098,
            'mondo_id' => 'MONDO:0011112',
            'gdm_uuid' => '1234',
        ]);

        $this->assertContains($this->curation->id, Curation::noUuid()->get()->pluck('id')->toArray());
    }

    /**
     * @test
     */
    public function can_scope_where_curation_does_have_uuid()
    {
        $curationWithUuid = factory(Curation::class)->create([
            'hgnc_id' => 17098,
            'mondo_id' => 'MONDO:0011112',
            'gdm_uuid' => '1234',
        ]);

        $this->assertEquals($curationWithUuid->id, Curation::hasUuid()->first()->id);
    }

    /**
     * @test
     */
    public function mondo_id_formatting_forced_when_set()
    {
        $curation = new Curation();

        $curation->mondo_id = 'mondo:0000000';

        $this->assertEquals('MONDO:0000000', $curation->mondo_id);
    }

    /**
     * @test
     */
    public function trims_gene_symbol_when_set()
    {
        $curation = new Curation();
        $curation->gene_symbol = ' BEANS ';

        $this->assertEquals('BEANS', $curation->gene_symbol);
    }

    /**
     * @test
     */
    public function can_add_an_upload_for_itself()
    {
        $curation = factory(Curation::class)->create([]);

        $upload = factory(Upload::class)->make(['file_name' => 'test_file_name']);
        $curation->addUpload($upload);

        $this->assertContains('test_file_name', $curation->uploads->pluck('file_name'));
    }

    /**
     * @test
     */
    public function can_remove_an_upload()
    {
        $curation = factory(Curation::class)->create();

        $upload = factory(Upload::class)->create(['curation_id' => $curation->id]);
        $curation->addUpload($upload);

        $curation->removeUpload($upload);

        $this->assertEquals(0, $curation->uploads->count());
        // $this->assertEquals(1, $curation->uploads->count());
        // $this->assertContains($upload->file_name, $curation->uploads->pluck('file_name'));
    }

    /**
     * @test
     * @group uuid
     */
    public function adds_uuid_when_creating_if_none_exists()
    {
        $curation = factory(Curation::class)->make(['uuid' => null]);
        $this->assertNull($curation->uuid);

        $curation->save();

        $this->assertNotNull($curation->uuid);
    }

    /**
     * @test
     * @group uuid
     */
    public function can_find_a_curation_by_uuid()
    {
        $uuid = Uuid::uuid4()->toString();
        $curations = factory(Curation::class, 5)->create();
        $curation = factory(Curation::class)->create(['uuid' => $uuid]);

        $this->assertNotNull(Curation::findByUuid($uuid));
    }

    /**
     * @test
     * @group curation-ownership
     */
    public function adds_a_curation_expert_panel_record_when_expert_panel_id_changed()
    {
        $ep1 = factory(ExpertPanel::class)->create();
        $curation = factory(Curation::class)->create(['expert_panel_id' => $ep1->id]);
        $this->assertEquals(1, $curation->fresh()->expertPanels()->count());

        // $ep2 = factory(ExpertPanel::class)->create();
        // $curation->update(['expert_panel_id' => $ep2->id]);
        // $this->assertEquals(2, $curation->fresh()->expertPanels()->count());
    }

    /**
     * @test
     */
    public function gets_phenotypes_not_included_in_curation()
    {
        $gene = factory(Gene::class)->create(['hgnc_id' => 6636, 'gene_symbol' => 'LMNA']);
        $ph1 = factory(Phenotype::class)->create();
        $ph2 = factory(Phenotype::class)->create();
        $gene->addPhenotype($ph1);
        $gene->addPhenotype($ph2);

        $curation = factory(Curation::class)->create([
            'gene_symbol' => 'LMNA',
            'hgnc_id' => 6636
        ]);
        $curation->addPhenotype($ph1);

        $this->assertEquals(collect($ph2->mim_number), $curation->fresh()->excludedPhenotypes->pluck('mim_number'));
    }
}
