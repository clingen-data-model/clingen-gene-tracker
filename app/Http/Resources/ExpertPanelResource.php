<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpertPanelResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        $data = parent::toArray($request);
        $data['working_group'] = new WorkingGroupResource($this->whenLoaded('workingGroup'));
        $data['curations'] = CurationResource::collection($this->whenLoaded('curations'));
        $data['users'] = UserResource::collection($this->whenLoaded('users'));
        $data['coordinators'] = UserResource::collection($this->whenLoaded('coordinators'));
        $data['curators'] = UserResource::collection($this->whenLoaded('curators'));

        return $data;
    }
}
