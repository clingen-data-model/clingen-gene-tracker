<?php

namespace App\Http\Resources;

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
        $data = parent::toArray($request);
        $data['curator'] = new UserResource($this->curator) ?? null;
        $data['expert_panel'] = new ExpertPanelResource($this->expertPanel) ?? null;
        $data['phenotypes'] = PhenotypeResource::collection($this->whenLoaded('phenotypes'));
        $data['rationales'] = RationaleResource::collection($this->whenLoaded('rationales'));
        $data['current_status'] = $this->whenLoaded('currentStatus');
        $data['curation_type'] = $this->whenLoaded('curationType');
        $data['created_at'] = $this->created_at;
        $data['updated_at'] = $this->updated_at;

        if ($this->curation_type_id == 3) {
            $data['isolated_phenotype'] = $this->phenotypes->first()->mim_number ?? null;
        }

        return $data;
    }
}
