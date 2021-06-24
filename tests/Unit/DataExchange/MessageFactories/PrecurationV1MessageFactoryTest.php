<?php

namespace Tests\Unit\DataExchange\MessageFactories;

use App\Gene;
use App\Curation;
use App\Phenotype;
use App\Rationale;
use Carbon\Carbon;
use Tests\TestCase;
use App\DataExchange\MessageFactories\PrecurationV1MessageFactory;

/**
 * @group data-exchange
 * @group outgoing-messages
 */
class PrecurationV1MessageFactoryTest extends TestCase
{
    public function setup():void
    {
        parent::setup();
        Carbon::setTestNow('2021-05-20');
        $this->curation = factory(Curation::class)->create();
        $this->factory = new PrecurationV1MessageFactory();
    }

    /**
     * @test
     */
    public function makesCreatedMessage()
    {
        $message = $this->factory->make($this->curation->fresh(), 'created');
        $data = $this->getBaseData();
        $this->assertMessageEquals('created', $data, $message);
    }

    /**
     * @test
     */
    public function makesUpdatedMessage()
    {
        $gene = factory(Gene::class)->create();
        $phs = factory(Phenotype::class, 3)->create();
        $phs->each(function ($ph) use ($gene) {
            $gene->addPhenotype($ph);
        });

        $this->curation->update([
            'hgnc_id' => $gene->hgnc_id,
            'gene_symbol' => $gene->gene_symbol,
            'pmids' => [1234,75830],
            'mondo_id' => 'MONDO:9999999',
            'disease_entity_notes' => 'test disease entity notes',
            'moi_id' => 2
        ]);
        $rationale = Rationale::first();
        $this->curation->rationales()->attach($rationale);
        $this->curation->addPhenotype($phs->first());

        $message = $this->factory->make($this->curation->fresh(), 'updated');

        $data = array_merge($this->getBaseData(), [
            'disease_entity' => [
                'mondo_id' => 'MONDO:9999999',
                'notes' => 'test disease entity notes'
            ],
            'mode_of_inheritance' => [
                'name' => $this->curation->moi->name,
                'hp_id' => $this->curation->moi->hp_id
            ],
            'rationales' => [
                'pmids' => $this->curation->pmids,
                'rationales' => $this->curation->fresh()->rationales->pluck('name')->toArray(),
                'notes' => null
            ],
            'omim_phenotypes' => [
                'included' => [$phs->first()->mim_number],
                'excluded' => $phs->take(-2)->sortBy('mim_number')->pluck('mim_number')->toArray()
            ]
        ]);

        $this->assertMessageEquals('updated', $data, $message);
    }

    /**
     * @test
     */
    public function makesDeletedMessage()
    {
        // $this->curation->delete();
        $message = $this->factory->make($this->curation, 'deleted');
        $this->assertMessageEquals('deleted', [
            'id' => $this->curation->id,
            'uuid' => $this->curation->uuid,
            'gene' => [
                'hgnc_id' => $this->curation->hgnc_id,
                'symbol' => $this->curation->gene_symbol
            ],
            'group' => [
                'id' => $this->curation->expertPanel->uuid,
                'name' => $this->curation->expertPanel->name,
                'affiliation_id' => $this->curation->expertPanel->affiliation->clingen_id
            ]
            ], $message);
    }
    


    private function assertMessageEquals($eventType, $data, $message)
    {
        $this->assertEquals($eventType, $message['event_type']);
        $this->assertEquals(1, $message['schema_version']);
        $this->assertEquals($data, $message['data']);
    }
    

    private function getBaseData()
    {
        return [
            'id' => $this->curation->id,
            'uuid' => $this->curation->uuid,
            'gene' => [
                'hgnc_id' => $this->curation->hgnc_id,
                'symbol' => $this->curation->gene_symbol,
            ],
            'group' => [
                'id' => $this->curation->expertPanel->uuid,
                'name' => $this->curation->expertPanel->name,
                'affiliation_id' => $this->curation->expertPanel->affiliation->clingen_id,
            ],
            'status' => [
                'name' => 'Uploaded',
                'effective_date' => Carbon::now()->toIsoString()
            ],
            'date_created' => Carbon::now()->toIsoString(),
            'date_updated' => Carbon::now()->toIsoString()
        ];
    }
    

    private function attrOrNull($obj, $attr)
    {
        return $obj ? $obj->{$attr} : null;
    }
}
