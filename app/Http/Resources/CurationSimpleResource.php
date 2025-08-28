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
        $curation_status = $this->currentStatus?->name;
        $classification_id = $classification = "";
        if($this->currentClassification) {
            $classification_id  = $this->currentClassification->id;
            $classification     = $this->currentClassification->name;
        }

        $hash   = hash('sha256', $this->gene_symbol . $disease?->mondo_id . $this->moi?->abbreviation . $classification . $curation_status);

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
            'curation_type'         => $this->curationType?->name,
            'curation_status'       => $curation_status,

            'date_approved'         => $this->currentStatusDate ?? null,

            'phenotypes'            => $this->whenLoaded('phenotypes', fn () => $this->phenotypes->map(fn ($p)   => "{$p->name} ({$p->mim_number})")->implode(', '), ''),
            'phenotypeIDs'          => $this->whenLoaded('phenotypes', fn () => $this->phenotypes->pluck('mim_number')->implode(', '), ''),

            'checkKey'              => $hash
        ];
    }
}
