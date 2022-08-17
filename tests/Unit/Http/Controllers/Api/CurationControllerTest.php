<?php

namespace Tests\Unit\Http\Controllers\Api;

use App\User;
use Carbon\Carbon;
use Tests\TestCase;
use App\CurationType;
use App\Clients\OmimClient;
use App\Clients\Omim\OmimEntry;
use App\Clients\Omim\OmimEntryContract;
use App\Rules\ValidGeneSymbolRule;
use App\Http\Resources\CurationResource;
use App\Rules\ValidHgncGeneSymbol;
use Illuminate\Foundation\Testing\DatabaseTransactions;

/**
 * @group api
 * @group curations
 * @group controllers
 * @group curations-controller
 */
class CurationControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function setUp(): void
    {
        parent::setUp();
        $this->user = factory(\App\User::class)->create();
        $this->curations = factory(\App\Curation::class, 10)->create(['curator_id' => $this->user->id]);
        $this->panel = factory(\App\ExpertPanel::class)->create();
        $this->rationale = factory(\App\Rationale::class)->create();
        $this->curationType = factory(\App\CurationType::class)->create();
    }

    /**
     * @test
     */
    public function index_lists_all_curations()
    {
        $curation = $this->curations->first();
        $curation->update([
            'curator_id' => $this->user->id,
            'expert_panel_id' => $this->panel->id,
        ]);

        $curationResource = new CurationResource($this->curations);
        $this->withoutExceptionHandling();
        $this->actingAs($this->user, 'api')
            ->json('GET', '/api/curations')
             ->assertStatus(200);
    }

    /**
     * @test
     */
    public function stores_new_curation()
    {
        $data = [
            'gene_symbol' => 'BRCA1',
            'expert_panel_id' => $this->panel->id,
            'nav' => 'next',
        ];

        $this->actingAs($this->user, 'api')
            ->json('POST', '/api/curations', $data)
            ->assertStatus(201)
            ->assertJsonFragment(['gene_symbol' => 'BRCA1']);
    }

    /**
     * @test
     */
    public function requires_gene_symbol()
    {
        $data = [
            'expert_panel' => $this->panel->id,
        ];

        $response = $this->actingAs($this->user, 'api')
            ->json('POST', '/api/curations/', $data)
            ->assertStatus(422)
            ->assertJson([
                'errors' => [
                    'gene_symbol' => [
                        'The gene symbol field is required.',
                    ],
                ],
            ]);
    }

    /**
     * @test
     */
    public function checks_for_valid_gene_symbol()
    {
        $data = [
            'gene_symbol' => 'MLTN1',
            'expert_panel' => $this->panel->id,
        ];

        $response = $this->actingAs($this->user, 'api')
            ->json('POST', '/api/curations/', $data)
            ->assertStatus(422)
            ->assertJson([
                'errors' => [
                    'gene_symbol' => ['MLTN1 is not a valid HGNC gene symbol.'],
                ],
            ]);
    }

    /**
     * @test
     */
    public function requires_expert_panel_id()
    {
        $data = [
            'gene_symbol' => 'BRCA1',
        ];

        $response = $this->actingAs($this->user, 'api')
            ->json('POST', '/api/curations/', $data)
            ->assertStatus(422)
            ->assertJson([
                'errors' => [
                    'expert_panel_id' => [
                        'The expert panel id field is required.',
                    ],
                ],
            ]);
    }

    /**
     * @test
     */
    public function index_does_not_include_phenotypes_when_not_requested()
    {
        $this->withoutExceptionHandling();
        $phenotypes = factory(\App\Phenotype::class, 3)->create();
        $this->curations->each(function ($t) use ($phenotypes) {
            $t->selectedPhenotypes()->sync($phenotypes->pluck('id'));
        });

        $response = $this->actingAs($this->user, 'api')
            ->json('GET', '/api/curations/')
            ->assertDontSee('"mim_number":"'.$phenotypes->first()->mim_number.'"');
    }

    /**
     * @test
     */
    public function index_includes_phenotypes_when_requested()
    {
        $phenotypes = factory(\App\Phenotype::class, 3)->create();
        $this->curations->each(function ($t) use ($phenotypes) {
            $t->selectedPhenotypes()->sync($phenotypes->pluck('id'));
        });

        $response = $this->actingAs($this->user, 'api')
            ->json('GET', '/api/curations/?with=phenotypes')
            ->assertSee('mim_number')
            ->assertSee('phenotypes')
            ->assertSee($phenotypes->first()->mim_number);
    }

    /**
     * @test
     */
    public function index_includes_status_by_default()
    {
        $this->withoutExceptionHandling();
        $status = factory(\App\CurationStatus::class)->create();
        $this->curations->each(function ($t) use ($status) {
            $t->curationStatuses()->attach($status->id);
        });

        $response = $this->actingAs($this->user, 'api')
            ->json('GET', '/api/curations')
            ->assertJsonFragment($status->getAttributes());
    }

    /**
     * @test
     */
    public function curation_show_includes_phenotypes_by_default()
    {
        $phenotypes = factory(\App\Phenotype::class, 3)->create();
        $this->curations->each(function ($t) use ($phenotypes) {
            $t->selectedPhenotypes()->sync($phenotypes->pluck('id'));
        });

        $response = $this->actingAs($this->user, 'api')
            ->json('GET', '/api/curations/'.$this->curations->first()->id)
            ->assertSee('mim_number')
            ->assertSee($phenotypes->first()->mim_number);
        // ->assertSee('"mim_number":"'.$phenotypes->first()->mim_number.'"');
    }

    /**
     * @test
     */
    public function curation_show_includes_rationales_by_default()
    {
        $this->curations->first()->rationales()->attach($this->rationale->id);
        $this->actingAs($this->user, 'api')
            ->json('GET', '/api/curations/'.$this->curations->first()->id)
            ->assertSee('rationales');
    }

    /**
     * @test
     */
    public function curation_show_includes_curationType_by_default()
    {
        $this->curations->first()->curationType()->associate($this->curationType);

        $this->actingAs($this->user, 'api')
            ->json('GET', '/api/curations/'.$this->curations->first()->id)
            ->assertSee('curation_type');
    }

    /**
     * @test
     */
    public function store_saves_phenotypes_for_new_curation()
    {
        $phenotype = factory(\App\Phenotype::class)->create();
        $curator = factory(\App\User::class)->create();

        $data = [
            'gene_symbol' => 'BRCA1',
            'expert_panel_id' => $this->panel->id,
            'curator_id' => $curator->id,
            'phenotypes' => [
                [
                    'mim_number' => 12345,
                    'name' => 'test pheno1',
                ],
                [
                    'mim_number' => 67890,
                    'name' => 'test pheno2',
                ],
                [
                    'mim_number' => $phenotype->mim_number,
                    'name' => $phenotype->name,
                ],
            ],
            'nav' => 'next',
        ];

        $this->actingAs($this->user, 'api')
            ->json('POST', '/api/curations', $data)
            ->assertJsonFragment(['mim_number'=>$phenotype->mim_number])
            ->assertJsonFragment(['mim_number'=>12345])
            ->assertJsonFragment(['mim_number'=>67890])
            ;
    }

    /**
     * @test
     */
    // public function store_saves_new_curation_status_when_set()
    // {
    //     $statuses = factory(\App\CurationStatus::class, 3)->create();
    //     $curator = factory(\App\User::class)->create();

    //     $data = [
    //         'gene_symbol' => 'BRCA1',
    //         'expert_panel_id' => $this->panel->id,
    //         'curator_id' => $curator->id,
    //         'curation_status_id' => $statuses->first()->id,
    //         'nav' => 'next',
    //     ];

    //     $this->actingAs($this->user, 'api')
    //         ->json('POST', '/api/curations', $data)
    //         ->assertStatus(201);

    //     $this->assertDatabaseHas('curation_curation_status', ['curation_status_id' => $statuses->first()->id]);
    // }

    /**
     * @test
     */
    // public function update_saves_new_curation_status_with_default_status_date()
    // {
    //     $this->assumeGeneSymbolValid();

    //     $statuses = factory(\App\CurationStatus::class, 3)->create();
    //     $curator = factory(\App\User::class)->create();

    //     $curation = $this->curations->first();
    //     $curation->curationStatuses()->attach([$statuses->first()->id => ['created_at' => today()->subDays(10)]]);

    //     $data = [
    //         'page' => 'info',
    //         'gene_symbol' => $curation->gene_symbol,
    //         'expert_panel_id' => $this->panel->id,
    //         'curator_id' => $curator->id,
    //         'curation_status_id' => $statuses->get(1)->id,
    //         'nav' => 'next',
    //     ];

    //     $this->withoutExceptionHandling();
    //     $this->actingAs($this->user, 'api')
    //         ->json('PUT', '/api/curations/'.$curation->id, $data)
    //         ->assertStatus(200);

    //     $this->assertDatabaseHas('curation_curation_status', ['curation_status_id' => $statuses->get(1)->id, 'created_at' => now()->format('Y-m-d H:i:s')]);
    // }

    /**
     * @test
     */
    // public function update_saves_new_curation_status_and_status_date_when_set()
    // {
    //     $this->assumeGeneSymbolValid();

    //     $statuses = factory(\App\CurationStatus::class, 3)->create();
    //     $curator = factory(\App\User::class)->create();

    //     $curation = $this->curations->first();
    //     $curation->curationStatuses()->attach([$statuses->first()->id => ['status_date' => today()->subDays(10)]]);

    //     Carbon::setTestNow('2019-01-01 00:00:00');
    //     $data = [
    //         'page' => 'info',
    //         'gene_symbol' => $curation->gene_symbol,
    //         'expert_panel_id' => $this->panel->id,
    //         'curation_type_id' => $this->curationType->id,
    //         'curator_id' => $curator->id,
    //         'curation_status_id' => 1,
    //         'curation_status_timestamp' => now()->subDays(2)->format('Y-m-d H:i:s'),
    //         'nav' => 'next',
    //     ];

    //     $this->withoutExceptionHandling();
    //     $response = $this->actingAs($this->user, 'api')
    //         ->json('PUT', '/api/curations/'.$curation->id, $data);
    //     $response->assertStatus(200);

    //     Carbon::setTestNow('2019-01-01 00:00:00');
    //     $this->assertDatabaseHas(
    //         'curation_curation_status',
    //         [
    //             'curation_id' => $curation->id,
    //             'curation_status_id' => 1,
    //             'status_date' => now()->subDays(2)->startOfDay()->format('Y-m-d H:i:s'),
    //         ]
    //     );
    // }
}
