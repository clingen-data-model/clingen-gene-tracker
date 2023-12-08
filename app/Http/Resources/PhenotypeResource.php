<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PhenotypeResource extends JsonResource
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
            // 'id' => $this->id,
            'mim_number' => $this->mim_number,
            'name' => $this->name,
            'curations' => CurationResource::collection($this->whenLoaded('curations')),
        ];
    }
}
