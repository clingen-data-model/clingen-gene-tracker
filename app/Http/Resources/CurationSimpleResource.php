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
        return [
            'id' => $this->id,
            'gene_symbol' => $this->gene_symbol,
            'disease' => $this->disease ? $this->disease->name : null,
            'mondo_id' => $this->disease ? $this->disease->mondo_id : null,
            'expert_panel' => $this->expertPanel ? (new ExpertPanelResource($this->expertPanel))->name : null,
            'moi' => $this->moi ? $this->moi->abbreviation : null,
            'classification' => $this->classifications && $this->classifications->count()
                                ? $this->classifications->pluck('name')->implode(', ')
                                : '',
            'curation_type' => $this->curationType ? $this->curationType->name : null,
            'current_status' => $this->currentStatus ? $this->currentStatus->name : null,
            'current_status_date' => Carbon::parse($this->currentStatusDate)->format('Y-m-d'),
            'phenotype' => $this->phenotypes && $this->phenotypes->count()
                            ? $this->phenotypes->map(fn($item) => "{$item->name} ({$item->mim_number})")->implode(', ')
                            : '',
        ];
    }
}
