<?php

namespace Tests\Unit\Http\Controllers\Api;

use App\Clients\Omim\OmimEntry;
use App\Clients\OmimClient;
use App\CurationType;
use App\Http\Resources\CurationResource;
use App\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

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

    // /**
    //  * @test
    //  */
    // public function index_lists_curations_filtered_by_gene_symbol()
    // {
    //     $this->withoutExceptionHandling();
    //     $testGene = 'BRCA1';
    //     $curation = factory(\App\Curation::class, 16)->create(['gene_symbol'=>$testGene]);

    //     $response = $this->actingAs($this->user, 'api')
    //         ->json('GET', '/api/curations?gene_symbol='.$testGene);

    //     $this->assertEquals(16, $response->original->count());
    // }

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
    public function requires_existing_curation_type_id_on_update()
    {
        $curation = factory(\App\Curation::class)->create(['curator_id' => $this->user->id]);
        $curationType = factory(\App\CurationType::class)->create();
        $data = [
            'gene_symbol' => 'BRCA1',
            'expert_panel_id' => $this->panel->id,
            'page' => 'curation-types',
            'nav' => 'next',
            'curation_type_id' => '',
        ];

        $response = $this->actingAs($this->user, 'api')
            ->json('PUT', '/api/curations/'.$curation->id, $data)
            ->assertStatus(422)
            ->assertJson([
                'errors' => [
                    'curation_type_id' => [
                        'A curation type is required to continue',
                    ],
                ],
            ]);

        $data['curation_type_id'] = $curationType->id;

        $this->withoutExceptionHandling();
        $response = $this->actingAs($this->user, 'api')
            ->json('PUT', '/api/curations/'.$curation->id, $data)
            ->assertStatus(200);
    }

    /**
     * @test
     */
    public function index_does_not_include_phenotypes_when_not_requested()
    {
        $this->withoutExceptionHandling();
        $phenotypes = factory(\App\Phenotype::class, 3)->create();
        $this->curations->each(function ($t) use ($phenotypes) {
            $t->phenotypes()->sync($phenotypes->pluck('id'));
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
            $t->phenotypes()->sync($phenotypes->pluck('id'));
        });

        $response = $this->actingAs($this->user, 'api')
            ->json('GET', '/api/curations/?with=phenotypes')
            ->assertSee('mim_number')
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
            $t->phenotypes()->sync($phenotypes->pluck('id'));
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
        // dd($this->curations->first()->curationType);
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

    /**
     * @test
     */
    public function updates_phenotypes_for_new_curation()
    {
        $phenotype = factory(\App\Phenotype::class)->create();
        $phenotype2 = factory(\App\Phenotype::class)->create();
        $this->curations->first()->phenotypes()->attach($phenotype2->id);

        $data = [
            'page' => 'phenotypes',
            'gene_symbol' => 'BRCA1',
            'expert_panel_id' => $this->panel->id,
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
            'rationales' => [['id' => $this->rationale->id]],
            'nav' => 'next',
        ];

        $this->actingAs($this->user, 'api')
            ->json('PUT', '/api/curations/'.$this->curations->first()->id, $data)
            ->assertStatus(200)
            ->assertJsonFragment(['mim_number'=>$phenotype->mim_number])
            ->assertJsonFragment(['mim_number'=>12345])
            ->assertJsonFragment(['mim_number'=>67890]);
    }

    /**
     * @test
     */
    public function store_transforms_comma_separated_pmds_into_array()
    {
        $this->assumeGeneSymbolValid();

        $data = array_merge($this->curations->first()->toArray(), [
            'page' => 'info',
            'pmids' => 'test,beans,monkeys',
        ]);
        $response = $this->actingAs($this->user, 'api')
            ->json('PUT', '/api/curations/'.$this->curations->first()->id, $data)
            ->assertJsonFragment(['pmids'=>["test","beans","monkeys"]]);

        $data = array_merge($this->curations->first()->toArray(), [
            'page' => 'info',
            'pmids' => ['test', 'beans', 'monkeys'],
        ]);
        $response = $this->actingAs($this->user, 'api')
            ->json('PUT', '/api/curations/'.$this->curations->first()->id, $data)
            ->assertJsonFragment(['pmids'=>["test","beans","monkeys"]]);
    }

    /**
     * @test
     */
    public function stores_isolated_phenotype_on_isolated_phenotype_curation()
    {
        $this->assumeGeneSymbolValid();
        app()->bind('App\Contracts\OmimClient', function ($app) {
            $stub = $this->createMock(OmimClient::class);
            $entryOutput = new OmimEntry(json_decode(file_get_contents(base_path('tests/files/omim_api/entry_response.json')))->omim->entryList[0]->entry);
            $stub->method('getEntry')->willReturn($entryOutput);

            return $stub;
        });

        $curation = $this->curations->first();
        $curation->update(['gene_symbol' => 'brca1']);

        $data = $curation->toArray();
        $data['page'] = 'phenotypes';
        $data['rationales'] = [$this->rationale];
        $data['isolated_phenotype'] = '100100';
        $data['nav'] = 'next';

        $this->withExceptionHandling();

        $response = $this->actingAs($this->user, 'api')
            ->json('PUT', '/api/curations/'.$curation->id, $data);

        $response->assertJsonFragment(['mim_number'=>100100]);
    }

    /**
     * @test
     */
    public function update_syncs_rationales_when_given()
    {
        $curation = $this->curations->first();
        $curation->update(['gene_symbol' => 'BRCA1']);
        $otherRationale = factory(\App\Rationale::class)->create();

        $data = $curation->toArray();
        $data['page'] = 'phenotypes';
        $data['rationales'] = [$this->rationale, $otherRationale];

        $response = $this->actingAs($this->user, 'api')
            ->json('PUT', '/api/curations/'.$curation->id, $data);
        $response->assertStatus(200);

        $this->assertEquals(collect([$this->rationale->id, $otherRationale->id]), $response->original->rationales->pluck('id'));
    }

    /**
     * @test
     * @group curation-validation
     */
    public function rationales_not_required_when_page_not_phenotypes()
    {
        $curation = $this->curations->first();
        $curation->update(['gene_symbol' => 'BRCA1']);

        $data = $curation->toArray();
        $data['page'] = 'info';
        $data['nav'] = 'next';

        $response = $this->actingAs($this->user, 'api')
            ->json('put', '/api/curations/'.$curation->id, $data)
            ->assertStatus(200);
    }

    /**
     * @test
     * @group curation-validation
     */
    public function rationales_not_required_when_curation_type_not_single_and_1_phenotype()
    {
        $this->markTestIncomplete('Can not test this b/c can not figure out how to mock OmimClient in http test');
        $curation = $this->curations->first();
        $curation->update([
            'curation_type_id' => 1,
            'gene_symbol' => 'BRCA2',
        ]);

        $curation->phenotypes()->sync([]);
        $data = $curation->toArray();
        $data['curation_type_id'] = 1;
        $data['page'] = 'phenotypes';
        $data['rationales'] = null;
        $data['nav'] = 'next';

        $mock = $this->createMock(OmimClient::class);
        $mock->method('getGenePhenotypes')
            ->willReturn(collect([1]));
        $mock->method('geneSymbolIsValid')
            ->willReturn(true);

        app()->instance('App\Contracts\OmimClient', $mock);

        $response = $this->actingAs($this->user, 'api')
            ->json('put', '/api/curations/'.$curation->id, $data);

        $response->assertStatus(200);
    }

    /**
     * @test
     * @group curation-validation
     */
    public function rationales_not_required_if_1_phenotype_and_type_single_omim()
    {
        $curation = $this->curations->first();
        $curation->update([
            'curation_type_id' => 1,
            'gene_symbol' => 'myl2',
        ]);

        app()->bind('App\Contracts\OmimClient', function ($app) {
            $stub = $this->createMock(OmimClient::class);
            $stub->method('geneSymbolIsValid')
                ->willReturn(true);
            $stub->method('getGenePhenotypes')
                ->willReturn(collect([1]));

            return $stub;
        });

        $data = $curation->toArray();
        $data['page'] = 'phenotypes';
        $data['rationales'] = null;
        $data['nav'] = 'next';

        $response = $this->actingAs($this->user, 'api')
            ->json('put', '/api/curations/'.$curation->id, $data)
            ->assertStatus(200);
    }

    /**
     * @test
     * @group curation-validation
     */
    public function rationales_required_if_1_phenotype_and_curation_type_other_than_single_omim()
    {
        $curation = $this->curations->first();
        $curation->update([
            'curation_type_id' => 2,
            'gene_symbol' => 'myl2',
        ]);

        app()->bind('App\Contracts\OmimClient', function ($app) {
            $stub = $this->createMock(OmimClient::class);
            $stub->method('geneSymbolIsValid')
                ->willReturn(true);
            $stub->method('getGenePhenotypes')
                ->willReturn(collect([1]));

            return $stub;
        });

        $data = $curation->toArray();
        $data['page'] = 'phenotypes';
        $data['rationales'] = null;
        $data['nav'] = 'next';

        $response = $this->actingAs($this->user, 'api')
            ->json('PUT', '/api/curations/'.$curation->id, $data);
        $response->assertStatus(422);

        $this->assertArrayHasKey('rationales', $response->original['errors']);
    }

    /**
     * @test
     * @group curation-validation
     */
    public function rationales_required_when_gene_has_more_than_1_phenotype()
    {
        app()->bind('App\Contracts\OmimClient', function ($app) {
            $stub = $this->createMock(OmimClient::class);
            $stub->method('geneSymbolIsValid')
                ->willReturn(true);
            $stub->method('getGenePhenotypes')
                ->willReturn(collect([1, 1]));

            return $stub;
        });

        $curation = $this->curations->first();
        $curation->update([
            'curation_type_id' => 1,
            'gene_symbol' => 'brca2',
        ]);

        $data = $curation->toArray();
        $data['page'] = 'phenotypes';
        $data['curation_type_id'] = 1;
        $data['rationales'] = null;
        $data['nav'] = 'next';

        $response = $this->actingAs($this->user, 'api')
            ->json('PUT', '/api/curations/'.$curation->id, $data);
        $response->assertStatus(422);

        $this->assertArrayHasKey('rationales', $response->original['errors']);
    }

    /**
     * @test
     * @group curation-validation
     */
    public function isolated_phenotype_required_when_curation_type_id_is_3()
    {
        $curation = $this->curations->first();
        $curation->update([
            'curation_type_id' => 3,
            'gene_symbol' => 'brca2',
        ]);

        $data = $curation->toArray();
        $data['page'] = 'phenotypes';
        $data['rationales'] = [1, 2];
        $data['isolated_phenotype'] = null;
        $data['nav'] = 'next';

        $response = $this->actingAs($this->user, 'api')
            ->json('PUT', '/api/curations/'.$curation->id, $data)
            ->assertStatus(422);

        $this->assertArrayHasKey('isolated_phenotype', $response->original['errors']);
    }

    /**
     * @test
     * @group curation-validation
     */
    public function isolated_phenotype_must_be_valid_mim_number_when_present()
    {
        $curation = $this->curations->first();
        $curation->update([
            'curation_type_id' => 3,
            'gene_symbol' => 'brca2',
        ]);

        $data = $curation->toArray();
        $data['page'] = 'phenotypes';
        $data['rationales'] = [1, 2];
        $data['isolated_phenotype'] = 123456;
        $data['nav'] = 'next';

        $response = $this->actingAs($this->user, 'api')
            ->json('PUT', '/api/curations/'.$curation->id, $data)
            ->assertStatus(422);

        $this->assertArrayHasKey('isolated_phenotype', $response->original['errors']);
    }

    /**
     * @test
     */
    public function hgnc_id_and_hgnc_name_are_ignored_when_updating_data()
    {
        $status = factory(\App\CurationStatus::class)->create();
        $curator = factory(\App\User::class)->create();
        $curation = $this->curations->first();
        $curation->update(['gene_symbol' => 'TP53', 'hgnc_id' => '777', 'hgnc_name' => 'if man is five']);

        $data = [
            'gene_symbol' => $curation->gene_symbol,
            'expert_panel_id' => $curation->expert_panel_id,
            'curator_id' => $curator->id,
            'curation_status_id' => $status->id,
            'nav' => 'next',
            'hgnc_id' => 666666,
            'hgnc_name' => 'beelzabub',
            'page' => 'info'
        ];
        $this->actingAs($this->user, 'api')
            ->json('PUT', '/api/curations/'.$curation->id, $data)
            // ->assertStatus(200)
            ->assertJsonFragment(['hgnc_id' => $curation->hgnc_id])
            ->assertJsonFragment(['hgnc_name' => $curation->hgnc_name]);
    }

    /**
     * @test
     * @group authorization
     */
    public function must_have_delete_permissions_to_delete_curation()
    {
        $curation = $this->curations->first();
        $curation->expertPanel->users()->attach($this->user->id, ['is_coordinator' => 0, 'can_edit_curations' => 1, 'is_curator' => 1]);

        $this->actingAs($this->user, 'api')
            ->json('DELETE', '/api/curations/'.$curation->id)
            ->assertStatus(403);
    }

    /**
     * @test
     * @group authorization
     */
    public function user_can_delete_a_curation_if_they_are_the_curator_of_curation_and_has_delete_permission()
    {
        $this->user->givePermissionTo('delete curations');
        $curation = $this->curations->first();

        $this->actingAs($this->user, 'api')
            ->json('DELETE', '/api/curations/'.$curation->id)
            ->assertStatus(200);
    }

    /**
     * @test
     * @group authorization
     */
    public function user_can_delete_a_curation_if_they_are_a_coordinator_of_expert_panel_that_owns_the_curation()
    {
        //create a coordinator who's not the curator
        $coordinator = factory(User::class)->create();

        // Get a curation and make the coordinator a coordinator on the
        // associated expert panel
        $curation = $this->curations->first();
        $curation->expertPanel->addCoordinator($coordinator);

        $this->actingAs($coordinator, 'api')
            ->json('DELETE', '/api/curations/'.$curation->id)
            ->assertStatus(200);
    }

    /**
     * @test
     * @group authorization
     */
    public function user_with_panel_curation_edit_perms_and_delete_curation_permission_can_delete_a_curation()
    {
        //create a user who's not the curator
        $user = factory(User::class)->create();
        $user->givePermissionTo('delete curations');

        // Get a curation and make the user a user on the
        // associated expert panel
        $curation = $this->curations->first();
        $curation->expertPanel->users()->attach($user->id, ['can_edit_curations' => 1]);

        $this->actingAs($user, 'api')
            ->json('DELETE', '/api/curations/'.$curation->id)
            ->assertStatus(200);
    }
}
