<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class UploadResource extends JsonResource
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
        $data = parent::toArray($request);
        $data['file_url'] = url(Storage::url('curator_uploads/'.$this->id.'/file'));
        $data['category'] = new DefaultResource($this->whenLoaded('category'));
        $data['uploader'] = new DefaultResource($this->whenLoaded('uploader'));

        return $data;
    }
}
