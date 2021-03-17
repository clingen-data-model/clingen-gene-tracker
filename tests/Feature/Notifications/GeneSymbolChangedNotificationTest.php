<?php

namespace Tests\Feature\Notifications;

use App\Gene;
use App\User;
use App\Curation;
use Tests\TestCase;
use App\ExpertPanel;
use Illuminate\Support\Facades\Event;
use App\Events\Genes\GeneSymbolChanged;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Notification;
use App\Notifications\Curations\GeneSymbolUpdated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Listeners\Curations\AugmentWithHgncAndMondoInfo;

/**
 * @group hgnc
 */
class GeneSymbolChangedNotificationTest extends TestCase
{
    use DatabaseTransactions;

    public function setup():void
    {
        parent::setup();
        $this->gene = factory(Gene::class)->create(['gene_symbol' => 'MLTN1', 'hgnc_id'=>9999999]);
        $this->ep = factory(ExpertPanel::class)->create();
        $this->coordinator = factory(User::class)->create();
        $this->ep->addCoordinator($this->coordinator);
        $this->curation = factory(Curation::class)->create(['gene_symbol' => 'MLTN1', 'hgnc_id'=>9999999, 'expert_panel_id'=>$this->ep->id]);
    }

    /**
     * @test
     */
    public function coordinator_is_notified_when_gene_symbol_changes()
    {
        Notification::fake();
        $this->gene->update(['gene_symbol'=>'BIRD1']);
        Notification::assertSentTo($this->coordinator, GeneSymbolUpdated::class);
    }
}
