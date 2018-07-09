<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\ExpertPanel;
use App\Services\BulkCurationProcessor;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BulkCurationProcessorTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->data = [
            "gene_symbol" => "BRCA1",
            "curator_email" => "sirs@unc.edu",
            "expert_panel_id" => 1,
            "curation_type" => "single-omim",
            "omim_id_1" => 93849384,
            "mondo_id" => null,
            "disease_entity_if_there_is_no_mondo_id" => null,
            "rationale_1" => "Assertion",
            "rationale_2" => "Molecular mechanism",
            "rationale_notes" => "notes on the rationale",
            "date_uploaded" => '2016-01-01',
            "precuration_date" => '2016-01-02',
            "disease_entity_assigned_date" => '2016-01-10',
            "curation_in_progress_date" => null,
            "curation_provisional_date" => null,
            "curation_approved_date" => null,
        ];
        $this->svc = new BulkCurationProcessor();
    }
    

    /**
     * @test
     */
    public function validates_valid_row_data()
    {
        // known good row data
        $this->assertTrue($this->svc->rowIsValid($this->data));
    }

    /**
     * @test
     */
    public function checks_curator_address_in_users()
    {
        $this->data['curator_email'] = null;
        $this->assertTrue($this->svc->rowIsValid($this->data));

        // curator email not found
        $this->data['curator_email'] = 'unfound.email@email.com';
        $this->assertFalse($this->svc->rowIsValid($this->data));
    }

    /**
     * @test
     */
    public function checks_expert_panel_id_exists()
    {
        $this->data['expert_panel_id'] = null;
        $this->assertFalse($this->svc->rowIsValid($this->data));

        $this->data['expert_panel_id'] = 9999999;
        $this->assertFalse($this->svc->rowIsValid($this->data));

        $panel = factory(ExpertPanel::class)->create();
        $this->data['expert_panel_id'] = $panel->id;
        $this->assertFalse($this->svc->rowIsValid($this->data));
    }

    /**
     * @test
     */
    public function checks_curation_type_is_valid()
    {
        $this->assertTrue($this->svc->rowIsValid($this->data));
        
        $this->data['curation_type'] = null;
        $this->assertTrue($this->svc->rowIsValid($this->data));

        $this->data['curation_type'] = 'beans-for-lunch';
        $this->assertFalse($this->svc->rowIsValid($this->data));
    }

    /**
     * @test
     */
    public function checks_rationales_are_valid()
    {
        $this->assertTrue($this->svc->rowIsValid($this->data));
        
        $this->data['rationale_1'] = null;
        $this->assertTrue($this->svc->rowIsValid($this->data));
        
        $this->data['rationale_2'] = 'Bobs yer uncle';
        $this->assertFalse($this->svc->rowIsValid($this->data));
    }
}
