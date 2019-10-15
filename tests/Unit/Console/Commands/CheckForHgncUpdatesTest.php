<?php

namespace Tests\Unit\Console\Commands;

use App\Console\Commands\CheckForHgncUpdates;
use App\User;
use App\Curation;
use Tests\TestCase;
use Tests\HasHgncClient;
use App\Contracts\HgncClient;
use App\ExpertPanel;
use App\Mail\Curations\GeneSymbolUpdated;
use App\Mail\HgncIdNotFoundNotification;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class CheckForHgncUpdatesTest extends TestCase
{
    use DatabaseTransactions;
    use HasHgncClient;

    public function setUp():void
    {
        parent::setUp();
        \DB::statement('SET FOREIGN_KEY_CHECKS=0');
        Curation::truncate();
        \DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    

    /**
     * @test
     */
    public function it_does_nothing_to_curations_whos_gene_symbols_have_not_changed()
    {
        $curation = factory(Curation::class)->create([
            'gene_symbol' => 'TH',
            'hgnc_id' => 11782
        ]);

        Mail::fake();
        $cmd = new CheckForHgncUpdates();
        $cmd->handle($this->getClient([new Response(200, [], file_get_contents(base_path('tests/files/hgnc_api/ht.json')))]));
        $this->assertDatabaseHas('curations', $curation->getAttributes());
        Mail::assertNothingSent();
    }

    /**
     * @test
     */
    public function it_updates_gene_symbol_and_mails_coordinators_if_gene_symbol_has_been_updated_on_hgnc()
    {
        $user = factory(User::class)->create();
        $expertPanel = factory(ExpertPanel::class)->create();
        $user->expertPanels()->sync([$expertPanel->id => ['is_coordinator' => true]]);

        $curation = factory(Curation::class)->create([
            'gene_symbol' => 'FYB',
            'hgnc_id' => 4036,
            'expert_panel_id' => $expertPanel->id
        ]);
        Mail::fake();
        $cmd = new CheckForHgncUpdates();
        $cmd->handle($this->getClient([new Response(200, [], file_get_contents(base_path('tests/files/hgnc_api/prev_symbol.json')))]));

        $this->assertDatabaseHas('curations', ['id' => $curation->id, 'gene_symbol' => 'FYB1']);
        Mail::assertSent(GeneSymbolUpdated::class);
    }
    
    /**
     * @test
     */
    public function sends_email_to_coordinator_if_hgnc_id_null_and_symbol_cant_be_found()
    {
        $user = factory(User::class)->create();
        $expertPanel = factory(ExpertPanel::class)->create();
        $user->expertPanels()->sync([$expertPanel->id => ['is_coordinator' => true]]);

        $curation = factory(Curation::class)->create([
            'gene_symbol' => 'MLTN',
            'hgnc_id' => null,
            'expert_panel_id' => $expertPanel->id
        ]);
        Mail::fake();
        $cmd = new CheckForHgncUpdates();
        $cmd->handle($this->getClient([
            new Response(200, [], file_get_contents(base_path('tests/files/hgnc_api/numFound0.json'))),
            new Response(200, [], file_get_contents(base_path('tests/files/hgnc_api/numFound0.json'))),
            new Response(200, [], file_get_contents(base_path('tests/files/hgnc_api/numFound0.json'))),
            new Response(200, [], file_get_contents(base_path('tests/files/hgnc_api/numFound0.json'))),
            new Response(200, [], file_get_contents(base_path('tests/files/hgnc_api/numFound0.json'))),
        ]));

        $this->assertDatabaseHas('curations', ['id' => $curation->id, 'gene_symbol' => 'MLTN', 'hgnc_id'=>null]);
        Mail::assertSent(HgncIdNotFoundNotification::class);
    }
}
