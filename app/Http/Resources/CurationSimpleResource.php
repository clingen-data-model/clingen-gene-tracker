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
            'expert_panel' => $this->expertPanel ? (new ExpertPanelResource($this->expertPanel))->name : null,
            'current_status' => $this->currentStatus ? $this->currentStatus->name : null,
            'current_status_date' => Carbon::parse($this->currentStatusDate)->format('Y-m-d'),
        ];
    }
}
