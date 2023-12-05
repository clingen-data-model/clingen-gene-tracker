<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DefaultResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $array = parent::toArray($request);

        unset($array['created_at']);
        unset($array['updated_at']);
        unset($array['deleted_at']);

        return $array;
    }
}
