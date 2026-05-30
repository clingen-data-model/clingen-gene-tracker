<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class WorkingGroupExpertPanelResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'long_base_name' => $this->long_base_name ?? null,
            'short_base_name' => $this->short_base_name ?? null,
            'affiliation_id' => $this->affiliation_id ?? null,

            'curations_count' => $this->curations_count ?? 0,

            'users' => UserResource::collection(
                $this->whenLoaded('users')
            ),
        ];
    }
}