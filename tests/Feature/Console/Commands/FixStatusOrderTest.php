<?php

namespace Tests\Feature\Console\Commands;

use App\Curation;
use Carbon\Carbon;
use Tests\TestCase;
use App\CurationStatus;
use App\Jobs\Curations\AddStatus;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FixStatusOrderTest extends TestCase
{
    public function setup():void
    {
        parent::setup();
        $this->curation = factory(Curation::class)->make();
        $this->curation->created_at = '2000-01-01';
        $this->curation->save();

        $this->statuses = CurationStatus::all()->keyBy('id');
        
        Bus::dispatch(
            new AddStatus(
                $this->curation,
                $this->statuses->get(config('curations.statuses.published')),
                Carbon::parse('2000-02-01')
            )
        );
    }

    /**
     * @test
     */
    public function adjusts_uploaded_at_date_if_current_status_and_after_more_advance_status()
    {
        Artisan::call('curations:order-statuses');

        $curation = $this->curation->fresh();
 
        $this->assertDatabaseHas('curation_curation_status', [
            'curation_id' => $this->curation->id,
            'curation_status_id' => config('curations.statuses.uploaded'),
            'status_date' => '2000-01-01'
        ]);

        $this->assertDatabaseHas('curations', [
            'id' => $this->curation->id,
            'curation_status_id' => config('curations.statuses.published')
        ]);
    }
}
