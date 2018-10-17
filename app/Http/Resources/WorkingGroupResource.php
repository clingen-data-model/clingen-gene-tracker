<?php

namespace App\Http\Resources;

use App\Http\Resources\ExpertPanelResource;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkingGroupResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $data = parent::toArray($request);
        $data['expert_panels'] = ExpertPanelResource::collection($this->whenLoaded('expertPanels'));
        return $data;
    }
}
