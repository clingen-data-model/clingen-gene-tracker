<?php

namespace Tests\Feature\Console\Commands;

use App\AppState;
use App\Phenotype;
use Carbon\Carbon;
use Tests\TestCase;
use Tests\SeedsGenes;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Event;
use App\Console\Commands\UpdateOmimData;
use Illuminate\Support\Facades\Notification;
use App\Events\Phenotypes\PhenotypeAddedForGene;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Notifications\Curations\PhenotypeAddedForCurationNotification;

/**
 * @group omim
 * @group phenotypes
 */
#[\PHPUnit\Framework\Attributes\Group('omim')]
#[\PHPUnit\Framework\Attributes\Group('phenotypes')]

class UpdateOmimDataTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsGenes;

    public function setup(): void
    {
        parent::setup();

        AppState::findByName('last_genemap_download')->update(['value' => Carbon::parse('2021-03-28')]);

        $testGeneMap = file_get_contents(
            base_path('tests/files/omim_api/genemap2.txt')
        );

        $httpClient = \Mockery::mock(ClientInterface::class);

        $httpClient
            ->shouldReceive('get')
            ->andReturnUsing(function ($url, $options = []) use ($testGeneMap) {
                if (isset($options['sink'])) {
                    file_put_contents($options['sink'], $testGeneMap);
                }

                return new Response(200);
            });

        app()->instance(ClientInterface::class, $httpClient);

        $this->dateGenerated = Carbon::parse('2021-03-29');

        $this->seedGenes();
    }
    
    /**
     * @test
     */
#[\PHPUnit\Framework\Attributes\Test]

    public function downloads_omim_geneamp2_file_and_stores_phenotypes()
    {
        AppState::findByName('last_genemap_download')
            ->update(['value' => Carbon::parse('2021-03-28')]);

        $this->artisan('omim:update-data', [
            '--file' => base_path('tests/files/omim_api/genemap2.txt'),
        ])
        ->assertExitCode(\Illuminate\Console\Command::SUCCESS);

        $this->assertEquals(24, Phenotype::count());

        $this->assertEquals(
            12,
            \DB::table('gene_phenotype')
                ->get()
                ->groupBy('hgnc_id')
                ->count()
        );
    }

    /**
     * @test
     */
#[\PHPUnit\Framework\Attributes\Test]

    public function adds_phenotype_moi_if_exists_on_row()
    {
        $this->artisan('omim:update-data');
        $this->assertDatabaseHas('phenotypes', [
            'mim_number' => 605429,
            'moi' => 'Autosomal dominant'
        ]);
    }
    

    /**
     * @test
     */
#[\PHPUnit\Framework\Attributes\Test]

    public function processes_if_newer_than_last_download()
    {
        AppState::findByName('last_genemap_download')->update(['value'=>Carbon::parse('2021-03-28')]);
        $this->artisan('omim:update-data');
        $this->assertEquals(24, Phenotype::count());
        $this->assertEquals(12, \DB::table('gene_phenotype')->groupBy()->get()->groupBy('hgnc_id')->count());
    }

    /**
     * @test
     */
#[\PHPUnit\Framework\Attributes\Test]

    public function updates_phenotype_if_already_exists()
    {
        factory(Phenotype::class)->create([
            'mim_number' => 610798,
            'name' => ':Immunodeficiency due to defect in MAPBP-interacting protei',
        ]);
        AppState::findByName('last_genemap_download')->update(['value'=>Carbon::parse('2021-03-28')]);
        $this->artisan('omim:update-data');
        $this->assertDatabaseHas('phenotypes', [
            'mim_number' => 610798,
            'name' => ':Immunodeficiency due to defect in MAPBP-interacting protein',
        ]);
    }

    /**
     * @test
     */
#[\PHPUnit\Framework\Attributes\Test]

    public function queries_phenotype_by_name_if_multiple_with_same_mim_number()
    {
        factory(Phenotype::class)->create([
            'mim_number' => 612069,
            'name' => 'Frontotemporal lobar degeneration, TARDBP-related',
        ]);
        factory(Phenotype::class)->create([
            'mim_number' => 612069,
            'name' => 'Amyotrophic lateral sclerosis 10, with or without FTD',
        ]);
        AppState::findByName('last_genemap_download')->update(['value'=>Carbon::parse('2021-03-28')]);
        $this->artisan('omim:update-data');
        $this->assertDatabaseHas('phenotypes', [
            'mim_number' => 612069,
            'name' => 'Amyotrophic lateral sclerosis 10, with or without FTD',
            'moi' => 'Autosomal dominant'
        ]);
    }

    /**
     * @test
     */
#[\PHPUnit\Framework\Attributes\Test]

    public function sets_new_last_genemap_download_if_newer()
    {
        AppState::findByName('last_genemap_download')->update(['value'=>Carbon::parse('2021-03-28')]);
        $this->artisan('omim:update-data');

        $this->assertDatabaseHas('app_states', [
            'name' => 'last_genemap_download',
            'value' => '2021-03-29 00:00:00'
        ]);
    }
    
    /**
     * @test
     */
#[\PHPUnit\Framework\Attributes\Test]

    public function does_not_process_if_not_newer_than_last_download()
    {
        AppState::findByName('last_genemap_download')->update(['value'=>Carbon::parse('2021-03-29')]);
        $this->artisan('omim:update-data');
        $this->assertEquals(0, Phenotype::count());
        $this->assertEquals(0, \DB::table('gene_phenotype')->groupBy()->get()->groupBy('hgnc_id')->count());
    }

    /**
     * @test
     */
#[\PHPUnit\Framework\Attributes\Test]

    public function gets_gene_symbol_from_approved_symbol_or_approved_gene_symbol_index()
    {
        $command = new UpdateOmimData();

        $this->assertEquals('BOB', $this->invokeMethod($command, 'getGeneSymbol', [['approved_symbol' => 'BOB']]));
        $this->assertEquals('BOB', $this->invokeMethod($command, 'getGeneSymbol', [['approved_gene_symbol' => 'BOB']]));
    }

    /**
     * @test
     */
#[\PHPUnit\Framework\Attributes\Test]

    public function fires_PhenotypeAddedForGene_if_new_phenotype_added_to_gene()
    {
        Event::fake();
        $this->artisan('omim:update-data');
        Event::assertDispatched(PhenotypeAddedForGene::class);
    }
    
    /**
     * @test
     */
#[\PHPUnit\Framework\Attributes\Test]

    public function notification_staged_for_coordinator_when_phenotype_added_to_curated_gene()
    {
        $user = $this->setupUser();
        $curation = $this->setupCuration(['hgnc_id' => 30478]);
        $curation->expertPanel->addCoordinator($user);

        Notification::fake();
        $this->artisan('omim:update-data');
        Notification::assertSentTo($user, PhenotypeAddedForCurationNotification::class, function ($notification) use ($user, $curation) {
            return $notification->toArray($user)['template'] == 'email.digest.phenotype_added';
        });
    }

    /**
     * @test
     */
#[\PHPUnit\Framework\Attributes\Test]

    public function phenotype_added_email_template_renders()
    {
        $curation = $this->setupCuration(['hgnc_id' => 30478]);
        $phenotype = factory(Phenotype::class)->create();

        $view = view('email.curations.phenotype_added', compact('curation', 'phenotype'));
        $html = $view->render();
        $expected = 'OMIM has added a new phenotype, '.$phenotype->name.', for '.$curation->gene_symbol.'. You may want to review your <a href="'.url('/#/curations/'.$curation->id).'">curation for '.$curation->gene_symbol.'</a>.';
        $this->assertEquals($expected, $html);
    }
}
