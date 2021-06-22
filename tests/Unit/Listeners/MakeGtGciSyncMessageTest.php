<?php

namespace Tests\Unit\Listeners;

use App\Curation;
use Tests\TestCase;
use Tests\SeedsGenes;
use App\CurationStatus;
use App\Events\Curation\Saved;
use App\Jobs\Curations\AddStatus;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class MakeGtGciSyncMessageTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsGenes;

    protected $fakeCurationSavedEvent = false;

    public function setup():void
    {
        parent::setup();
        // \Log::info('start test...');
        $this->status = CurationStatus::find(config('curations.statuses.precuration-complete'));

        $this->genes = $this->seedGenes();
        $this->curation = factory(Curation::class)->create(['gene_symbol' => $this->genes->last()->gene_symbol, 'hgnc_id' => $this->genes->last()->hgnc_id, 'hgnc_name' => $this->genes->last()->name]);  
    }

    /**
     * @test
     */
    public function no_gt_gci_messages_created_if_status_not_precuration_complete()
    {
        $this->curation->update(['moi_id' => 1, 'mondo_id' => 'MONDO:0000336']);
        $this->assertDatabaseMissing('stream_messages', [
            'topic' => config('dx.topics.outgoing.gt-gci-sync')
        ]);
    }

    /**
     * @test
     */
    public function no_gt_gci_messages_created_if_mondo_id_not_set()
    {
        $this->curation->update(['moi_id' => 1]);
        $status = CurationStatus::find(config('curations.statuses.precuration-complete'));
        Bus::dispatch(new AddStatus($this->curation, $status));
        $this->assertDatabaseMissing('stream_messages', [
            'topic' => config('dx.topics.outgoing.gt-gci-sync')
        ]);
    }

    /**
     * @test
     */
    public function no_gt_gci_messages_created_if_moi_id_not_set()
    {
        $this->curation->update(['mondo_id' => 'MONDO:0000336']);
        $status = CurationStatus::find(config('curations.statuses.precuration-complete'));
        Bus::dispatch(new AddStatus($this->curation, $status));
        $this->assertDatabaseMissing('stream_messages', [
            'topic' => config('dx.topics.outgoing.gt-gci-sync')
        ]);
    }

    /**
     * @test
     */
    public function message_type_is_precuration_completed_if_mondo_was_null ()
    {
        $this->setPrecurationCompleteStatus();

        $this->addMoi();

        $this->assertDatabaseMissing('stream_messages', [
            'topic' => config('dx.topics.outgoing.gt-gci-sync')
        ]);

        $this->addMondo();

        $this->assertDatabaseHas('stream_messages', [
            'topic' => config('dx.topics.outgoing.gt-gci-sync'),
            'message->event_type' => 'precuration_completed',
            'message->data->uuid' => $this->curation->uuid
        ]);

    }
    
    /**
     * @test
     */
    public function event_type_is_precuration_completed_if_moi_was_null ()
    {
        $this->setPrecurationCompleteStatus();

        $this->addMondo();

        $this->assertDatabaseMissing('stream_messages', [
            'topic' => config('dx.topics.outgoing.gt-gci-sync')
        ]);

        $this->addMoi();

        $this->assertDatabaseHas('stream_messages', [
            'topic' => config('dx.topics.outgoing.gt-gci-sync'),
            'message->event_type' => 'precuration_completed',
            'message->data->uuid' => $this->curation->uuid
        ]);
    }

    /**
     * @test
     */
    public function event_type_is_precuration_completed_if_has_gdm_and_status_changed_to_precuration_complete()
    {
        $this->addMoi();
        $this->addMondo();

        $this->assertDatabaseMissing('stream_messages', [
            'topic' => config('dx.topics.outgoing.gt-gci-sync')
        ]);


        $this->setPrecurationCompleteStatus();
        $this->assertDatabaseHas('stream_messages', [
            'topic' => config('dx.topics.outgoing.gt-gci-sync'),
            'message->event_type' => 'precuration_completed',
            'message->data->uuid' => $this->curation->uuid
        ]);
    }

    /**
     * @test
     */
    public function creates_gdm_updated_event_if_curation_has_gdm_and_is_complete_and_mondo_changes()
    {
        $this->addMoiAndMondo();
        $this->setPrecurationCompleteStatus();

        $this->curation->update(['mondo_id' => 'MONDO:0000339']);

        $this->assertDatabaseHas('stream_messages', [
            'topic' => config('dx.topics.outgoing.gt-gci-sync'),
            'message->event_type' => 'gdm_updated',
            'message->data->uuid' => $this->curation->uuid
        ]);
    }
    
    /**
     * @test
     */
    public function creates_gdm_updated_event_if_curation_has_gdm_and_is_complete_and_moi_changes()
    {
        $this->addMoiAndMondo();
        $this->setPrecurationCompleteStatus();

        $this->curation->update(['moi_id' => 4]);

        $this->assertDatabaseHas('stream_messages', [
            'topic' => config('dx.topics.outgoing.gt-gci-sync'),
            'message->event_type' => 'gdm_updated',
            'message->data->uuid' => $this->curation->uuid
        ]);
    }
    
    

    private function setPrecurationCompleteStatus()
    {
        // Log::debug('add PrecurationCompleteStatus);
        Bus::dispatch(new AddStatus($this->curation, $this->status));
        $this->curation = $this->curation->fresh();
    }

    private function addMondo() {
        // Log::debug('addMondo');
        $this->curation->update(['mondo_id' => 'MONDO:0000336']);
    }

    private function addMoi()
    {
        // Log::debug('addMoi');
        $this->curation->update(['moi_id' => 2]);
    }

    private function addMoiAndMondo()
    {
        $this->curation->update([
            'mondo_id' => 'MONDO:0000336',
            'moi_id' => 2
        ]);
    }
    
    
    
    
    
}
