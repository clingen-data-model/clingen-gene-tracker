<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CurationResource extends JsonResource
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
        $data['curator'] = new UserResource($this->curator) ?? null;
        // $data['mondo_id'] = $this->disease ?? null;
        $data['expert_panel'] = new ExpertPanelResource($this->expertPanel) ?? null;
        $data['phenotypes'] = PhenotypeResource::collection($this->whenLoaded('phenotypes'));
        $data['included_phenotypes'] = PhenotypeResource::collection($this->whenLoaded('includedPhenotypes'));
        $data['excluded_phenotypes'] = PhenotypeResource::collection($this->whenLoaded('excludedPhenotypes'));
        $data['rationales'] = RationaleResource::collection($this->whenLoaded('rationales'));
        $data['mode_of_inheritance'] = $this->whenLoaded('modeOfInheritance');
        $data['moi'] = $this->whenLoaded('moi');
        $data['classifications'] = $this->whenLoaded('classifications');
        $data['disease'] = $this->whenLoaded('disease');
        $data['current_status'] = ($this->currentStatus && $this->currentStatus->id) ? $this->currentStatus : null;
        $data['current_status_date'] = ($this->currentStatus && $this->currentStatus->id) ? $this->currentStatusDate : null;
        $data['current_classification'] = $this->currentClassification->id ? $this->currentClassification : null;
        $data['curation_type'] = $this->whenLoaded('curationType');
        $data['curation_type'] = $this->whenLoaded('curationType');
        $data['created_at'] = $this->created_at;
        $data['updated_at'] = $this->updated_at;

        if ($this->curation_type_id == 3) {
            $data['isolated_phenotype'] = $this->includedPhenotypes->first()->mim_number ?? null;
        }

        return $data;
    }
}
