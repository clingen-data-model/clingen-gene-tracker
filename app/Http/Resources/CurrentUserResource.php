<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CurrentUserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $data = parent::toArray($request);
        $data['roles'] = $this->whenLoaded('roles', $this->roles);
        $data['permissions'] = $this->whenLoaded('permissions', $this->permissions);
        $data['preferences'] = $this->whenLoaded('preferences', PreferenceResource::collection($this->preferences));

        return $data;
    }
}
