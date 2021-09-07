<?php

namespace Tests\Unit\Models;

use App\Gene;
use App\Disease;
use Tests\TestCase;
use App\StreamError;
use App\ModeOfInheritance;
use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class StreamErrorTest extends TestCase
{
    use DatabaseTransactions;
    
    public function setup():void
    {
        parent::setup();
        $this->disease = factory(Disease::class)->create([
            'mondo_id' => 'MONDO:0005260',
            'name' => 'Bob'
        ]);
        $this->gene = factory(Gene::class)->create([
            'hgnc_id' => '29221',
            'gene_symbol' => 'Dobbs'
        ]);
        $this->streamError = factory(StreamError::class)->create(['message_payload' => [
            "date" => "2020-02-07T16:14:56.867Z",
            "status" => "created",
            "report_id" => "7f7dbbf2-984a-4bf5-b7d5-d0ea76636045",
            "contributors" => [
                [
                    "id" => "63776290-d39c-434c-841e-23626e0631f1",
                    "name" => "Isabelle Thiffault",
                    "email" => "ithiffault@cmh.edu",
                    "roles" => [
                        "creator"
                    ]
                ]
            ],
            "performed_by" => [
                "id" => "63776290-d39c-434c-841e-23626e0631f1",
                "name" => "Isabelle Thiffault",
                "email" => "ithiffault@cmh.edu",
                "on_behalf_of" => [
                    "id" => "",
                    "name" => ""
                ]
            ],
            "gene_validity_evidence_level" => [
                "evidence_level" => "",
                "gene_validity_sop" => "",
                "genetic_condition" => [
                    "gene" => "HGNC:29221",
                    "condition" => "MONDO:0005260",
                    "mode_of_inheritance" => "HP:0000006"
                ]
            ]
            ]]);

            // dd($this->streamError->message_payload->gene_validity_evidence_level->genetic_condition );
    }
    
    /**
     * @test
     */
    public function stream_error_belongs_to_a_gene()
    {
        $this->assertEquals('Dobbs', $this->streamError->geneModel->gene_symbol);
    }

    /**
     * @test
     */
    public function stream_error_belongs_to_a_disease()
    {
        $this->assertEquals('Bob', $this->streamError->diseaseModel->name);
    }
    
    /**
     * @test
     */
    public function stream_error_belongs_to_an_moi()
    {
        $this->assertEquals(ModeOfInheritance::findByHpId($this->streamError->moi)->name, $this->streamError->moiModel->name);
    }
    
    

}
