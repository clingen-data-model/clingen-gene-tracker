<?php

namespace Tests\Unit\Jobs;

use App\User;
use App\Curation;
use App\Phenotype;
use Tests\TestCase;
use App\ExpertPanel;
use GuzzleHttp\Psr7\Response;
use Tests\Traits\GetsOmimClient;
use App\Jobs\Curations\UpdateOmimData;
use App\Jobs\SendCurationMailToCoordinators;
use App\Mail\Curations\PhenotypeOmimEntryMoved;
use App\Mail\Curations\PhenotypeNomenclatureUpdated;
use Illuminate\Foundation\Testing\DatabaseTransactions;

/**
 * @group omim
 */
class UpdateOmimDataTest extends TestCase
{
    use DatabaseTransactions;
    use GetsOmimClient;

    private $phenotype;

    public function setUp():void
    {
        parent::setUp();
        $this->phenotype = factory(Phenotype::class)->create([
            'mim_number' => 607084,
            'name' => 'beans!',
            'omim_entry' => [
                'prefix' => '#',
                'mimNumber' => 607084,
                'status' => "live",
                'titles' => [
                    'preferredTitle' => 'DEAFNESS, AUTOSOMAL RECESSIVE 31; DFNB31',
                    'alternativeTitles' => "WHIRLER, MOUSE, HOMOLOG OF"
                ]
            ]
        ]);

        $this->ep = factory(ExpertPanel::class)->create();
        $this->coordinator = factory(User::class)->create();
        $this->coordinator->expertPanels()->sync([
            $this->ep->id => ['is_coordinator' => true]
        ]);

        $this->curation = factory(Curation::class)->create(['expert_panel_id' => $this->ep->id]);
        $this->curation->phenotypes()->sync($this->phenotype->id);

        $this->phenotype = $this->phenotype->fresh();
    }

    /**
     * @test
     */
    public function updates_phenotype_name_to_preferredTitle_is_different_and_no_phenotypeMapList()
    {
        $jsonString = file_get_contents(base_path('tests/files/omim_api/607084.json'));
        $omim = $this->getOmimClient([
            new Response(200, [], $jsonString)
        ]);

        $job = new UpdateOmimData($this->phenotype);
        $job->handle($omim);

        $this->assertDatabaseHas('phenotypes', [
            'mim_number' => 607084,
            'name' => 'DEAFNESS, AUTOSOMAL RECESSIVE 31; DFNB31'
        ]);
    }

    /**
     * @test
     */
    public function updates_phenotype_name_to_geneMap_phenotypeMapList_first_phenotypeMap_phenotype_if_exists()
    {
        $jsonString = file_get_contents(base_path('tests/files/omim_api/607084_with_geneMap.json'));
        $omim = $this->getOmimClient([
            new Response(200, [], $jsonString)
        ]);

        $job = new UpdateOmimData($this->phenotype);
        $job->handle($omim);

        $this->assertDatabaseHas('phenotypes', [
            'mim_number' => 607084,
            'name' => 'Deafness, autosomal recessive 31'
        ]);
    }
    
    
    /**
     * @test
     */
    public function dispatches_email_to_coordinators_when_phenotype_name_changed()
    {
        \Mail::fake();

        $jsonString = file_get_contents(base_path('tests/files/omim_api/607084.json'));
        $omim = $this->getOmimClient([
            new Response(200, [], $jsonString)
        ]);

        $job = new UpdateOmimData($this->phenotype);
        $job->handle($omim);

        \Mail::assertSent(PhenotypeNomenclatureUpdated::class);
    }

    /**
     * @test
     */
    public function updates_mim_number_and_name_if_entry_moved()
    {
        $movedJson = file_get_contents(base_path('tests/files/omim_api/entry_moved.json'));
        $newJson = file_get_contents(base_path('tests/files/omim_api/139139.json'));
        $omim = $this->getOmimClient([
            new Response(200, [], $movedJson),
            new Response(200, [], $newJson)
        ]);

        $job = new UpdateOmimData($this->phenotype);
        $job->handle($omim);


        $this->assertDatabaseHas('phenotypes', [
            'mim_number' => 139139,
            'name' => 'NUCLEAR RECEPTOR SUBFAMILY 4, GROUP A, MEMBER 1; NR4A1'
        ]);
    }

    /**
     * @test
     */
    public function updates_curation_phenotype_relations_if_phenotype_moved_to_existing_phenotype()
    {
        $ph2 = factory(Phenotype::class)->create([
            'mim_number' => 139139,
            'name' => 'NUCLEAR RECEPTOR SUBFAMILY 4, GROUP A, MEMBER 1; NR4A1'
        ]);

        $movedJson = file_get_contents(base_path('tests/files/omim_api/entry_moved.json'));
        $newJson = file_get_contents(base_path('tests/files/omim_api/139139.json'));
        $omim = $this->getOmimClient([
            new Response(200, [], $movedJson),
            new Response(200, [], $newJson)
        ]);

        $job = new UpdateOmimData($this->phenotype);
        $job->handle($omim);

        $this->assertDatabaseHas('curation_phenotype', [
            'curation_id' => $this->curation->id,
            'phenotype_id' => $ph2->id
        ]);

        $this->assertDatabaseMissing('curation_phenotype', [
            'curation_id' => $this->curation->id,
            'phenotype_id' => $this->phenotype->id
        ]);
    }
    

    /**
     * @test
     */
    public function dispatches_email_to_coordinators_when_phentotype_entry_moved()
    {
        \Mail::fake();

        $movedJson = file_get_contents(base_path('tests/files/omim_api/entry_moved.json'));
        $newJson = file_get_contents(base_path('tests/files/omim_api/139139.json'));
        $omim = $this->getOmimClient([
            new Response(200, [], $movedJson),
            new Response(200, [], $newJson)
        ]);

        $job = new UpdateOmimData($this->phenotype);
        $job->handle($omim);

        \Mail::assertSent(PhenotypeOmimEntryMoved::class);
    }
}
