<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DefaultResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function toArray($request)
    {
        $array = parent::toArray($request);

        unset($array['created_at']);
        unset($array['updated_at']);
        unset($array['deleted_at']);

        return $array;
    }
}
