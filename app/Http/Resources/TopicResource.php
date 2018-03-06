<?php

namespace App\Http\Resources;

use App\Http\Resources\Users;
use Illuminate\Http\Resources\Json\JsonResource;

class TopicResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'gene_symbol' => $this->gene_symbol,
            'curator_id' => $this->curator_id,
            'curator' => new UserResource($this->curator) ?? null,
            'expert_panel_id' => $this->expert_panel_id,
            'expert_panel' => new ExpertPanelResource($this->expertPanel) ?? null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}
