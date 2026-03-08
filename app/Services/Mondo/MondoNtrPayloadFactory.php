<?php

namespace App\Services\Mondo;

use App\Curation;
use Illuminate\Support\Str;
use App\Services\Mondo\MondoNtrParsing;

final class MondoNtrPayloadFactory
{
    public function makeFromUi(array $data, Curation $curation, string $reqUuid): array
    {
        $defaultEmptyValue = 'n.a.';
        $curation->loadMissing('expertPanel.affiliation');

        $clingenId  = $curation->expertPanel?->affiliation?->clingen_id;
        $affiliationUrl = $clingenId ? "https://clinicalgenome.org/affiliation/{$clingenId}/" : $defaultEmptyValue;
        $parent     = MondoNtrParsing::parseMondoIdLabel($data['parent']);
        $children   = MondoNtrParsing::parseMondoList($data['children'] ?? [$defaultEmptyValue]);
        $synonyms   = MondoNtrParsing::splitCsv($data['synonyms'] ?? [$defaultEmptyValue]);
        $pmids      = $data['curation_pmids'];
        $commentsParts = [];
        if (!empty($data['comments'])) {
            $commentsParts[] = $data['comments'];
        }
        if (!empty($data['synonym_type'])) {
            $commentsParts[] = "Synonym type requested: {$data['synonym_type']}";
        }
        
        $commentsParts[] = "GT_REQUEST_UUID: {$reqUuid}";
        $submitters = [$affiliationUrl];
        if($data['orcid'] ?? false) {
            $submitters[] = $data['orcid'];
        }

        $payload = [
            'submitters' => $submitters,
            'gene_name' => (string) $curation->gene_symbol,
            'hgnc_id' => (string) $curation->hgnc_id,
            'gene_disease_refs' => $pmids,

            'parents' => [
                [
                    'label' => $parent['label'],
                    'id' => $parent['id'],
                ]
            ],

            'term_label' => (string) $data['label'],

            // optional fields
            'definition' => (string) $data['definition'],
            'comments' => implode("\n\n", $commentsParts),
        ];

        if (!empty($synonyms)) {
            $payload['synonyms'] = $synonyms;
        }

        if (!empty($children)) {
            $payload['children'] = $children;
        }

        return $payload;
    }

    public function makeDefaultTitle(array $payload): string
    {
        $gene = $payload['gene_name'] ?? 'GENE';
        $label = $payload['term_label'] ?? 'new disease term';

        // suggested: [ClinGen NTR] {GENE}-related {disease}
        // If label already begins with "{GENE}-related", strip it to avoid duplication.
        $disease = preg_replace('/^'.preg_quote($gene, '/').'\s*-?\s*related\s+/i', '', $label);

        return "[ClinGen NTR] {$gene}-related {$disease}";
    }
}