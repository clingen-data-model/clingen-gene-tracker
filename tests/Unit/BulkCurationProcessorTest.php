<?php

namespace Tests\Unit;

use Mockery;
use App\User;
use App\Curation;
use App\Phenotype;
use Tests\TestCase;
use App\ExpertPanel;
use App\Clients\OmimClient;
use App\Clients\Omim\OmimEntry;
use App\Clients\Omim\OmimEntryContract;
use App\Services\BulkCurationProcessor;
use App\Contracts\OmimClient as OmimClientContract;
use App\Exceptions\BulkUploads\InvalidFileException;
use Illuminate\Foundation\Testing\DatabaseTransactions;

/**
 * @group bulk-curations
 */
class BulkCurationProcessorTest extends TestCase
{
    use DatabaseTransactions;
    private $user, $ep, $data, $phenotype;

    public function setUp(): void
    {
        parent::setUp();
        $this->user = User::where('email', 'sirs@unc.edu')->first();
        if (!$this->user) {
            $this->user = factory(User::class)->create(['email' => 'sirs@unc.edu']);
        }
        $this->ep = factory(ExpertPanel::class)->create([]);
        $this->phenotype = factory(Phenotype::class)->create(['mim_number' => 605724]);

        app()->instance(OmimClient::class, new class extends OmimClient {
            public function geneSymbolisValid($symbol) 
            {
                return true;
            }
            public function getEntry($id): OmimEntryContract 
            {
                return new OmimEntry(json_decode(file_get_contents(base_path('tests/files/omim_api/entry_response.json')))->omim->entryList[0]->entry);;
            }
        });
        $this->data = [
            'gene_symbol' => 'BRCA1',
            'curator_email' => 'sirs@unc.edu',
            'curation_type' => 'single-omim',
            'omim_id_1' => 605724,
            'omim_id_2' => null,
            'omim_id_3' => null,
            'omim_id_4' => null,
            'omim_id_5' => null,
            'omim_id_6' => null,
            'omim_id_7' => null,
            'omim_id_8' => null,
            'omim_id_9' => null,
            'omim_id_10' => null,
            'mondo_id' => null,
            'disease_entity_if_there_is_no_mondo_id' => null,
            'rationale_1' => 'Assertion',
            'rationale_2' => 'Molecular mechanism',
            'rationale_3' => null,
            'rationale_4' => null,
            'rationale_5' => null,
            'rationale_notes' => 'notes on the rationale',
            'pmid_1' => 819281721,
            'pmid_2' => 123198121,
            'pmid_3' => null,
            'pmid_4' => null,
            'pmid_5' => null,
            'pmid_6' => null,
            'pmid_7' => null,
            'pmid_8' => null,
            'pmid_9' => null,
            'pmid_10' => null,
            'uploaded_date' => '2016-01-01',
            'precuration_date' => '2016-01-02',
            'disease_entity_assigned_date' => null,
            'curation_in_progress_date' => '2016-01-10',
            'curation_provisional_date' => null,
            'curation_approved_date' => null,
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
            $this->assertEquals(4, count($e->getValidationErrors()));
            $this->assertEquals(0, \DB::table('curations')->count());
        }
    }

    /**
     * @test
     */
    public function adds_new_curations_for_valid_file()
    {
        \DB::table('curations')->delete();
        $curations = $this->svc->processFile(base_path('tests/files/bulk_curation_upload_good.xlsx'), $this->ep->id);

        $this->assertEquals(3, \DB::table('curations')->count());
    }

    /**
     * @test
     */
    public function creates_curation_from_valid_row_data()
    {
        \DB::table('curations')->delete();
        $curation = $this->svc->processRow($this->data, $this->ep->id);
        $this->assertInstanceOf(Curation::class, $curation);
        $this->assertDatabaseHas(
            'curations',
            [
                'gene_symbol' => $this->data['gene_symbol'],
                'curation_type_id' => 1,
                'expert_panel_id' => $this->ep->id,
                'curator_id' => $this->user->id,
                'mondo_id' => $this->data['mondo_id'],
                'rationale_notes' => $this->data['rationale_notes'],
                'disease_entity_notes' => $this->data['disease_entity_if_there_is_no_mondo_id'],
                'rationale_notes' => $this->data['rationale_notes'],
            ]
        );

        // Separate assertion b/c assertion above doesn't like json field?
        $this->assertEquals([819281721, 123198121], $curation->pmids);

        $this->assertDatabaseHas('curation_rationale', [
            'curation_id' => $curation->id,
            'rationale_id' => 1,
        ]);
        $this->assertDatabaseHas('curation_rationale', [
            'curation_id' => $curation->id,
            'rationale_id' => 2,
        ]);

        $this->assertDatabaseHas('curation_curation_status', [
            'curation_status_id' => 1,
            'curation_id' => $curation->id,
        ]);
        $this->assertDatabaseHas('curation_curation_status', [
            'curation_status_id' => 2,
            'curation_id' => $curation->id,
        ]);
        $this->assertDatabaseHas('curation_curation_status', [
            'curation_status_id' => 4,
            'curation_id' => $curation->id,
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

    /**
     * @test
     */
    public function checks_omim_ids_are_valid_mim_numbers()
    {
        $this->assertTrue($this->svc->rowIsValid($this->data));

        $omimClientMock = Mockery::mock(OmimClientContract::class)->shouldIgnoreMissing();
        $omimClientMock->shouldReceive(['getEntry' => false]);

        app()->instance(OmimClient::class, $omimClientMock);

        $this->data['omim_id_1'] = 12983;
        $this->assertFalse($this->svc->rowIsValid($this->data));

        $this->data['omim_id_2'] = 'Bobs yer uncle';
        $this->assertFalse($this->svc->rowIsValid($this->data));
    }

    /**
     * @test
     */
    public function checks_gene_symbol_is_valid_hgnc_symbol()
    {
        $this->assertTrue($this->svc->rowIsValid($this->data));

        $omimClientMock = $this->createMock(OmimClient::class);
        $omimClientMock->method('geneSymbolIsValid')
            ->willReturn(false);

        app()->instance(OmimClient::class, $omimClientMock);
        $this->data['gene_symbol'] = 'Bobs yer uncle';
        $this->assertFalse($this->svc->rowIsValid($this->data));
        $this->assertTrue($this->svc->getValidationErrors()->contains('gene_symbol', 'Bobs yer uncle is not a valid HGNC gene symbol.'));
    }

    /**
     * @test
     */
    public function validates_column_headers_before_processing()
    {
        \DB::table('curations')->delete();

        try {
            $this->svc->processFile(base_path('tests/files/bulk_curation_upload_bad_header.xlsx'), 1);
            $this->fail('InvalidRowException not thrown for data with bad rows');
        } catch (InvalidFileException $e) {
            $this->assertEquals(2, count($e->getValidationErrors()));
            $this->assertEquals(0, \DB::table('curations')->count());
        }
    }

    /**
     * @test
     */
    public function validates_file_has_Curations_sheet()
    {
        \DB::table('curations')->delete();
        
        try {
            $this->svc->processFile(base_path('tests/files/bulk_curation_upload_bad_file.xlsx'), 1);
            $this->fail('InvalidFileException not thrown for bad file.');
        } catch (InvalidFileException $e) {
            $this->assertEquals(1, count($e->getValidationErrors()));
            $this->assertEquals(0, \DB::table('curations')->count());
        }
    }
    
}
