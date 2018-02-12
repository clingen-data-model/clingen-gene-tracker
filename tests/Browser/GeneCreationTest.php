<?php

namespace Tests\Browser;

use Tests\DuskTestCase;
use Laravel\Dusk\Browser;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class GeneCreationTest extends DuskTestCase
{
    use DatabaseMigrations;
    
    public function setUp()
    {
        parent::setUp();
        $this->user = factory(\App\User::class)->create();
        $this->panels = factory(\App\ExpertPanel::class, 3)->create();
    }
    /**
     * @test
     */
    public function a_user_can_create_a_new_gene_record()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                    ->visit('/')
                    ->assertSee('ClinGen')
                    ->waitFor('#new-gene-btn')
                    ->click('#new-gene-btn')
                    ->waitFor('#new-gene-form')
                    ->type('#gene-symbol-input', 'TEST-1')
                    ->select('#expert-panel-select', 1)
                    ->click('#new-gene-form-save')
                    ->assertSee('TEST-1')
                    ->assertSee($this->panels->first()->name);
        });
    }
}
