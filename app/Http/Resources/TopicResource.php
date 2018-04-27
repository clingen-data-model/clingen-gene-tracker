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
        $data['rationale'] = $this->whenLoaded('rationale');
        $data['topic_status'] = $this->whenLoaded('topicStatus');
        $data['curation_type'] = $this->whenLoaded('curationType');
        $data['created_at'] = $this->created_at;
        $data['updated_at'] = $this->updated_at;

        return $data;
    }
}
