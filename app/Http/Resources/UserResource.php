<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
        $data['roles'] = RoleResource::collection($this->whenLoaded('roles'));
        $data['expert_panels'] = ExpertPanelResource::collection($this->whenLoaded('expertPanels'));

        return $data;
    }
}
