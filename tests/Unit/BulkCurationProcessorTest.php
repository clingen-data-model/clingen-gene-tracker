<?php

namespace Tests\Unit;

use App\Curation;
use Tests\TestCase;
use App\ExpertPanel;
use App\Services\BulkCurationProcessor;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Exceptions\BulkUploads\InvalidRowException;
use App\Exceptions\BulkUploads\InvalidFileException;
use Illuminate\Foundation\Testing\DatabaseTransactions;

/**
 * @group bulk-curations
 */
class BulkCurationProcessorTest extends TestCase
{
    use DatabaseTransactions;

    public function setUp()
    {
        parent::setUp();
        $this->data = [
            "gene_symbol" => "BRCA1",
            "curator_email" => "sirs@unc.edu",
            "curation_type" => "single-omim",
            "omim_id_1" => 93849384,
            "mondo_id" => null,
            "disease_entity_if_there_is_no_mondo_id" => null,
            "rationale_1" => "Assertion",
            "rationale_2" => "Molecular mechanism",
            "rationale_notes" => "notes on the rationale",
            "date_uploaded" => '2016-01-01',
            "precuration_date" => '2016-01-02',
            "disease_entity_assigned_date" => null,
            "curation_in_progress_date" => '2016-01-10',
            "curation_provisional_date" => null,
            "curation_approved_date" => null,
        ];
        $this->svc = new BulkCurationProcessor();
    }
    

    /**
     * @test
     */
    public function returns_validation_errors_if_file_has_invalid_rows()
    {
        \DB::table('curations')->delete();

        try {
            $this->svc->processFile(base_path('tests/files/bulk_curation_upload_bad.xlsx'), 1);
            $this->fail('InvalidRowException not thrown for data with bad rows');
        } catch (InvalidFileException $e) {
            $this->assertEquals(3, count($e->getValidationErrors()));
            $this->assertEquals(0, \DB::table('curations')->count());
        }
    }
    
    /**
     * @test
     */
    public function adds_new_curations_for_valid_file()
    {
        \DB::table('curations')->delete();
        $this->svc->processFile(base_path('tests/files/bulk_curation_upload_good.xlsx'), 1);

        $this->assertEquals(3, \DB::table('curations')->count());
    }

    /**
     * @test
     */
    public function throws_exception_when_processing_invalid_row()
    {
        $this->data['curator_email'] = 'beans@beans.com';
        try {
            $this->svc->processRow($this->data, 1);
            $this->fail('InvalidRowException not thrown for bad row data');
        } catch (InvalidRowException $e) {
            $this->assertEquals($e->getRowData(), $this->data);
            $this->assertEquals($e->getValidationErrors(), collect(
                [['curator_email' => 'Curator Email '.$this->data['curator_email'].' was not found in the system']]
            ));
        }
    }

    /**
     * @test
     */
    public function creates_curation_from_valid_row_data()
    {
        $curation = $this->svc->processRow($this->data, 1);
        $this->assertInstanceOf(Curation::class, $curation);
        $this->assertDatabaseHas(
            'curations',
            [
                'gene_symbol' => $this->data['gene_symbol'],
                'curator_id' => 1,
                'curation_type_id' => 1,
                'expert_panel_id' => 1,
                'rationale_notes' => $this->data['rationale_notes'],
                'mondo_id' => $this->data['mondo_id'],
                'disease_entity_notes' => $this->data['disease_entity_if_there_is_no_mondo_id'],
                'rationale_notes' => $this->data['rationale_notes'],
            ]
        );
        $this->assertDatabaseHas('curation_rationale', [
            'curation_id' => $curation->id,
            'rationale_id' => 1
        ]);
        $this->assertDatabaseHas('curation_rationale', [
            'curation_id' => $curation->id,
            'rationale_id' => 2
        ]);

        $this->assertDatabaseHas('curation_curation_status', [
            'curation_status_id' => 1,
            'curation_id' => $curation->id
        ]);
        $this->assertDatabaseHas('curation_curation_status', [
            'curation_status_id' => 2,
            'curation_id' => $curation->id
        ]);
        $this->assertDatabaseHas('curation_curation_status', [
            'curation_status_id' => 4,
            'curation_id' => $curation->id
        ]);
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
