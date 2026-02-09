<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class CurationSimpleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $disease    = $this->disease;
        $moiRel     = $this->modeOfInheritance;
        $curation_status    = $this->currentStatus?->name;
        $classification_id  = $classification = "";
        if($this->currentClassification) {
            $classification_id  = $this->currentClassification->id;
            $classification     = $this->currentClassification->name;
        }

        $hash   = hash('sha256', $this->uuid . $this->gene_symbol . $disease?->mondo_id . $this->moi?->abbreviation . $classification . $curation_status);

        $phenotypesText = '';
        $phenotypeIDs   = '';
        $genePhenotypes = collect();
        if ($this->relationLoaded('gene') && $this->gene && $this->gene->relationLoaded('phenotypes')) {
            $genePhenotypes = $this->gene->phenotypes;
        }
        $excludePhenotypes  = $genePhenotypes->values();

        if ($this->relationLoaded('phenotypes')) {
            $phenotypes     = $this->phenotypes;
            $phenotypesText = $phenotypes->map(fn ($phenotype) => "{$phenotype->name} ({$phenotype->mim_number})")->implode(', ');
            $phenotypeIDs   = $phenotypes->pluck('mim_number')->implode(', ');

            $selectedIds            = $phenotypes->pluck('id')->all();
            $excludePhenotypesText  = $genePhenotypes
                                        ->reject(fn ($phenotype) => in_array(data_get($phenotype, 'id'), $selectedIds, true))
                                        ->map(fn ($phenotype) => data_get($phenotype, 'name') . ' (' . data_get($phenotype, 'mim_number') . ')')
                                        ->implode(', ');
        } else {
            $excludePhenotypesText = $excludePhenotypes->map(fn ($phenotype) => data_get($phenotype, 'name') . ' (' . data_get($phenotype, 'mim_number') . ')')->implode(', ');
        }

        return [
            'curation_id'           => $this->uuid,
            'gene_symbol'           => $this->gene_symbol,

            
            "hgnc_id"               => $this->hgnc_id,
            "hgnc_name"             => $this->hgnc_name,

            'disease_name'          => $disease?->name,
            'mondo_id'              => $disease?->mondo_id,

            'expert_panel'          => $this->expertPanel?->name,

            'moi'                   => $this->moi?->abbreviation,
            'moi_name'              => $moiRel?->name,
            'hp_id'                 => $moiRel?->hp_id,

            'classification_id'     => $classification_id,
            'classification'        => $classification,

            'curation_status_id'    => $this->curation_status_id,
            'curation_type_short'   => $this->curationType?->name,
            'curation_type'         => $this->curationType?->description,
            'curation_status'       => $curation_status,

            'date_approved'         => $this->currentStatusDate ?? null,

            'phenotypes'            => $phenotypesText,
            'phenotypeIDs'          => $phenotypeIDs,
            'excluded_phenotypes'   => $excludePhenotypesText,

            'rationales'            => $this->whenLoaded('rationales', fn () => $this->rationales->map(fn ($p)   => $p->name)->implode(', '), ''),

            'checkKey'              => $hash
        ];
    }
}
