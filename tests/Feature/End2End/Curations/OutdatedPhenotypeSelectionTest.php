<?php

namespace Tests\Feature\End2End\Curations;

use App\Curation;
use App\Gene;
use App\Phenotype;
use App\User;
use Tests\TestCase;

class OutdatedPhenotypeSelectionTest extends TestCase
{
    private Curation $curation;
    private Phenotype $outdated;
    private Phenotype $current;

    public function setUp(): void
    {
        parent::setUp();
        $this->assumeGeneSymbolValid();
        $user = factory(User::class)->create();
        $gene = factory(Gene::class)->create();
        $this->curation = factory(Curation::class)->create([
            'gene_symbol' => $gene->gene_symbol,
            'hgnc_id' => $gene->hgnc_id,
            'curator_id' => $user->id,
        ]);
        $this->outdated = factory(Phenotype::class)->create(['label_obsolete_at' => now(),
            'mim_number' => 613834, 'name' => 'Multisystemic smooth muscle dysfunction syndrome']);
        $this->current = factory(Phenotype::class)->create(['mim_number' => 613834,
            'name' => 'Smooth muscle dysfunction syndrome']);
        $gene->phenotypes()->sync([$this->outdated->id, $this->current->id]);
        $this->curation->phenotypes()->sync([$this->outdated->id]);
        $this->actingAs($user, 'api');
    }

    private function saveSelection(array $extra)
    {
        return $this->putJson('/api/curations/'.$this->curation->id, array_merge([
            'gene_symbol' => $this->curation->gene_symbol,
            'expert_panel_id' => $this->curation->expert_panel_id,
            'page' => 'phenotypes',
        ], $extra));
    }

    public function test_removal_updates_saved_associations_and_both_page_endpoints(): void
    {
        $this->getJson('/api/curations/'.$this->curation->id)->assertOk()
            ->assertJsonPath('data.phenotypes.0.label_obsolete', true);
        $this->getJson('/api/omim/curation/'.$this->curation->id)->assertOk()
            ->assertJsonCount(2, 'phenotypes');

        $this->saveSelection(['phenotypes' => [['id' => $this->current->id]]])->assertOk()
            ->assertJsonCount(1, 'data.phenotypes')
            ->assertJsonPath('data.phenotypes.0.id', $this->current->id);
        $this->assertEquals([$this->current->id], $this->curation->fresh()->phenotypes->pluck('id')->all());
        $this->getJson('/api/curations/'.$this->curation->id)->assertOk()
            ->assertJsonCount(1, 'data.phenotypes')
            ->assertJsonPath('data.phenotypes.0.label_obsolete', false);
        $this->getJson('/api/omim/curation/'.$this->curation->id)->assertOk()
            ->assertJsonCount(1, 'phenotypes')
            ->assertJsonPath('phenotypes.0.id', $this->current->id);
        $this->assertNotNull($this->outdated->fresh());
    }

    public function test_client_cannot_retain_outdated_ids_even_by_falsifying_the_status(): void
    {
        $this->saveSelection(['phenotypes' => [
            ['id' => $this->outdated->id, 'label_obsolete' => false],
            ['id' => $this->current->id],
        ]])->assertOk()->assertJsonCount(1, 'data.phenotypes')
            ->assertJsonPath('data.phenotypes.0.id', $this->current->id);
        $this->getJson('/api/curations/'.$this->curation->id)->assertOk()
            ->assertJsonCount(1, 'data.phenotypes')->assertJsonPath('data.phenotypes.0.id', $this->current->id);
        $this->getJson('/api/omim/curation/'.$this->curation->id)->assertOk()
            ->assertJsonCount(1, 'phenotypes');
        $this->assertTrue($this->outdated->fresh()->label_obsolete);
        $this->assertFalse($this->current->fresh()->label_obsolete);
    }

    public function test_unrelated_updates_preserve_historical_selections_with_or_without_the_selection_payload(): void
    {
        foreach ([[], ['phenotypes' => []], ['phenotypes' => [['id' => $this->outdated->id, 'label_obsolete' => true]]]] as $selection) {
            $this->saveSelection(array_merge($selection, ['page' => 'info', 'curation_notes' => 'Updated notes']))
                ->assertOk()->assertJsonPath('data.phenotypes.0.id', $this->outdated->id);
            $this->assertEquals([$this->outdated->id], $this->curation->fresh()->phenotypes->pluck('id')->all());
        }
    }

    public function test_explicit_empty_selection_removes_all_associations(): void
    {
        $this->saveSelection(['phenotypes' => []])->assertOk()->assertJsonCount(0, 'data.phenotypes');
        $this->assertCount(0, $this->curation->fresh()->phenotypes);
        $this->getJson('/api/omim/curation/'.$this->curation->id)->assertOk()
            ->assertJsonCount(1, 'phenotypes')->assertJsonPath('phenotypes.0.id', $this->current->id);
    }

    public function test_invalid_selection_and_permission_failures_preserve_associations(): void
    {
        $this->saveSelection(['phenotypes' => [['id' => 2147483647]]])->assertUnprocessable();
        $this->assertEquals([$this->outdated->id], $this->curation->fresh()->phenotypes->pluck('id')->all());
        $this->actingAs(factory(User::class)->create(), 'api');
        $this->saveSelection(['phenotypes' => [['id' => $this->current->id]]])->assertForbidden();
        $this->assertEquals([$this->outdated->id], $this->curation->fresh()->phenotypes->pluck('id')->all());
    }

    public function test_isolated_phenotype_resolves_the_current_record_with_the_same_mim_number(): void
    {
        $this->saveSelection(['isolated_phenotype' => 613834])->assertOk()
            ->assertJsonPath('data.phenotypes.0.id', $this->current->id);
    }

    public function test_obsolete_only_isolated_phenotype_is_rejected_without_removal(): void
    {
        $this->current->update(['label_obsolete_at' => now()]);
        $this->saveSelection(['phenotypes' => [], 'isolated_phenotype' => 613834])->assertUnprocessable();
        $this->assertEquals([$this->outdated->id], $this->curation->fresh()->phenotypes->pluck('id')->all());
    }
}
