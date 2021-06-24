<?php

namespace Tests\Unit\Models;

use App\Disease;
use Tests\TestCase;
use Illuminate\Support\Facades\Event;
use App\Events\Disease\DiseaseNameChanged;
use App\Events\Disease\MondoTermObsoleted;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class DiseaseTest extends TestCase
{
    use DatabaseTransactions;
    
    public function setup():void
    {
        parent::setup();
        $this->disease = factory(Disease::class)->create(['is_obsolete' => 0]);
    }
    

    /**
     * @test
     */
    public function dispatches_DiseaseNameChanged_when_nomenclature_changes()
    {
        $oldName = $this->disease->name;

        Event::fake(DiseaseNameChanged::class);
        $this->disease->update(['name' => 'Some new name']);
        Event::assertDispatched(DiseaseNameChanged::class);
        Event::assertDispatched(DiseaseNameChanged::class, function ($event) use ($oldName) {
            return $event->disease == $this->disease && $event->oldName == $oldName;
        });
    }

    /**
     * @test
     */
    public function dispatches_MondoTermObsoleted_when_obsoleted_changes_from_0_to_1()
    {

        Event::fake(MondoTermObsoleted::class);
        $this->disease->update(['is_obsolete' => 1]);
        Event::assertDispatched(MondoTermObsoleted::class);
        Event::assertDispatched(MondoTermObsoleted::class, function ($event) {
            return $event->disease == $this->disease;
        });
    }
    
    
}
