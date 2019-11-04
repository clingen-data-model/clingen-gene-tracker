<?php

namespace Tests\Unit\Jobs;

use App\User;
use App\Curation;
use App\Phenotype;
use Tests\TestCase;
use App\ExpertPanel;
use App\Jobs\Curation\UpdateOmimData;
use App\Mail\Curation\PhenotypeNomenclatureUpdated;
use GuzzleHttp\Psr7\Response;
use Tests\Traits\GetsOmimClient;
use Illuminate\Foundation\Testing\DatabaseTransactions;

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
    }

    /**
     * @test
     */
    public function updates_phenotype_name_if_preferredTitle_is_different()
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
    public function dispatches_email_to_coordinators_when_phenotype_name_changed()
    {
        \Mail::fake();

        $ep = factory(ExpertPanel::class)->create();
        $coordinator = factory(User::class)->create();
        $coordinator->expertPanels()->sync([
            $ep->id => ['is_coordinator' => true]
        ]);

        $curation = factory(Curation::class)->create(['expert_panel_id' => $ep->id]);
        $curation->phenotypes()->sync($this->phenotype->id);

        $this->phenotype = $this->phenotype->fresh();

        $jsonString = file_get_contents(base_path('tests/files/omim_api/607084.json'));
        $omim = $this->getOmimClient([
            new Response(200, [], $jsonString)
        ]);

        $job = new UpdateOmimData($this->phenotype);
        $job->handle($omim);

        \Mail::assertSent(PhenotypeNomenclatureUpdated::class, 1);
    }
    
    
}
