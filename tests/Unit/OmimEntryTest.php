<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Clients\Omim\OmimEntry;
use App\Exceptions\OmimResponseException;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group omim
 * @group clients
 * @group omim-entry
 */
class OmimEntryTest extends TestCase
{
    /**
     * @test
     */
    public function gets_phenotypeMapList_if_entry_has_geneMap()
    {
        $response = json_decode(file_get_contents(base_path('tests/files/omim_api/gene_phenotypes_search.json')));
        $rawEntry = $response->omim->searchResponse->entryList[0]->entry;
        $omimEntry = new OmimEntry($rawEntry);

        $this->assertEquals(11, count($omimEntry->getPhenotypeMapList()));
    }

    /**
     * @test
     */
    public function gets_phenotypeMapList_if_entry_has_only_phenotypeMap()
    {
        $rawEntry = json_decode('{
            "prefix": "#",
            "mimNumber": 600105,
            "status": "live",
            "titles": {
              "preferredTitle": "RETINITIS PIGMENTOSA 12; RP12",
              "alternativeTitles": "RETINITIS PIGMENTOSA WITH OR WITHOUT PARAARTERIOLAR PRESERVATION OF RETINAL PIGMENT EPITHELIUM;;\nRP WITH OR WITHOUT PRESERVED PARAARTERIOLE RETINAL PIGMENT EPITHELIUM;;\nRP WITH OR WITHOUT PPRPE"
            },
            "phenotypeMapList": [
              {
                "phenotypeMap": {
                  "mimNumber": 604210,
                  "phenotype": "Retinitis pigmentosa-12",
                  "phenotypeMimNumber": 600105,
                  "phenotypeMappingKey": 3,
                  "phenotypeInheritance": "Autosomal recessive",
                  "phenotypicSeriesNumber": "PS268000",
                  "sequenceID": 5260,
                  "chromosome": 1,
                  "chromosomeSymbol": "1",
                  "chromosomeSort": 1410,
                  "chromosomeLocationStart": 197201503,
                  "chromosomeLocationEnd": 197478454,
                  "transcript": "ENST00000367400.8",
                  "cytoLocation": "1q31-q32.1",
                  "computedCytoLocation": "1q31.3",
                  "geneSymbols": "CRB1, RP12, LCA8"
                }
              }
            ],
            "matches": "pigmentosa, retiniti, rp"
          }');
        $entry = new OmimEntry($rawEntry);

        $this->assertEquals(1, count($entry->getPhenotypeMapList()));
    }

    /**
     * @test
     */
    public function gets_moi_if_set()
    {
        $rawEntry = json_decode('{
            "prefix": "#",
            "mimNumber": 600105,
            "status": "live",
            "titles": {
              "preferredTitle": "RETINITIS PIGMENTOSA 12; RP12",
              "alternativeTitles": "RETINITIS PIGMENTOSA WITH OR WITHOUT PARAARTERIOLAR PRESERVATION OF RETINAL PIGMENT EPITHELIUM;;\nRP WITH OR WITHOUT PRESERVED PARAARTERIOLE RETINAL PIGMENT EPITHELIUM;;\nRP WITH OR WITHOUT PPRPE"
            },
            "phenotypeMapList": [
              {
                "phenotypeMap": {
                  "mimNumber": 604210,
                  "phenotype": "Retinitis pigmentosa-12",
                  "phenotypeMimNumber": 600105,
                  "phenotypeMappingKey": 3,
                  "phenotypeInheritance": "Autosomal recessive",
                  "phenotypicSeriesNumber": "PS268000",
                  "sequenceID": 5260,
                  "chromosome": 1,
                  "chromosomeSymbol": "1",
                  "chromosomeSort": 1410,
                  "chromosomeLocationStart": 197201503,
                  "chromosomeLocationEnd": 197478454,
                  "transcript": "ENST00000367400.8",
                  "cytoLocation": "1q31-q32.1",
                  "computedCytoLocation": "1q31.3",
                  "geneSymbols": "CRB1, RP12, LCA8"
                }
              }
            ],
            "matches": "pigmentosa, retiniti, rp"
          }');
        $entry = new OmimEntry($rawEntry);

        $this->assertEquals('Autosomal recessive', $entry->getMoi());
    }
    

    /**
     * @test
     */
    public function throws_OmimResponseException_if_phenotypeMapList_is_empty()
    {
        $rawEntry = json_decode('{
            "prefix": "#",
            "mimNumber": 268000,
            "status": "live",
            "titles": {
              "preferredTitle": "RETINITIS PIGMENTOSA; RP"
            },
            "phenotypeMapList": [],
            "matches": "pigmentosa, retiniti, rp"
          }');

        $entry = new OmimEntry($rawEntry);

        $this->expectException(OmimResponseException::class);

        $entry->getPhenotypeMapList();
    }
}
